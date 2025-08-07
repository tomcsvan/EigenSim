<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/auth.php';
include 'common/db.php';

$user_id = $_SESSION['user_id'];
$backtest_id = $_POST['backtest_id'] ?? null;
$strategy_id = $_POST['strategy_id'] ?? null;
$initial_capital = $_POST['initial_capital'] ?? null;

if (!$backtest_id || !$strategy_id || !$initial_capital || !$user_id) {
    die("Missing input data.");
}

// Get backtest and strategy info
$stmt = oci_parse($conn, "
    SELECT b.ticker_symbol, b.start_date, b.end_date, s.strategy_type
    FROM Backtest b
    JOIN Strategy s ON s.strategy_id = :sid
    WHERE b.backtest_id = :bid
");
oci_bind_by_name($stmt, ':sid', $strategy_id);
oci_bind_by_name($stmt, ':bid', $backtest_id);
oci_execute($stmt);

if (!($info = oci_fetch_assoc($stmt))) {
    die("Invalid backtest or strategy.");
}

$ticker = $info['TICKER_SYMBOL'];
$start = $info['START_DATE'];
$end = $info['END_DATE'];
$type = $info['STRATEGY_TYPE'];

$started_date = date('Y-m-d', strtotime($start)) . ' 00:00:00';
$ended_date   = date('Y-m-d', strtotime($end)) . ' 23:59:59';

// Fetch price history
$stmt = oci_parse($conn, "
    SELECT TO_CHAR(trade_time, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS trade_time_str,
           open_price, high_price, low_price, close_price, volume
    FROM PriceHistory
    WHERE ticker_symbol = :ticker
        AND trade_time BETWEEN TO_TIMESTAMP(:start_dt, 'YYYY-MM-DD HH24:MI:SS')
        AND TO_TIMESTAMP(:end_dt, 'YYYY-MM-DD HH24:MI:SS')
    ORDER BY trade_time
");
oci_bind_by_name($stmt, ':ticker', $ticker);
oci_bind_by_name($stmt, ':start_dt', $started_date);
oci_bind_by_name($stmt, ':end_dt', $ended_date);
oci_execute($stmt);

$all_rows = [];
while ($row = oci_fetch_assoc($stmt)) {
    $all_rows[] = [
        $row['TRADE_TIME_STR'] . ":00Z",
        $row['OPEN_PRICE'],
        $row['HIGH_PRICE'],
        $row['LOW_PRICE'],
        $row['CLOSE_PRICE'],
        $row['VOLUME']
    ];
}

$history_rows = array_slice($all_rows, 0, 390);
$current_rows = array_slice($all_rows, 390);

// Write history.csv
$history_path = "/tmp/history_{$backtest_id}.csv";
$fh = fopen($history_path, 'w');
fputcsv($fh, ['time', 'open', 'high', 'low', 'close', 'volume']);
foreach ($history_rows as $row) fputcsv($fh, $row);
fclose($fh);

// Write current.csv
$current_path = "/tmp/input_{$backtest_id}.csv";
$fc = fopen($current_path, 'w');
fputcsv($fc, ['time', 'open', 'high', 'low', 'close', 'volume']);
foreach ($current_rows as $row) fputcsv($fc, $row);
fclose($fc);

// Prepare engine command
$prompt_path = null;
$engine_path = ($type === 'custom')
    ? "/home/t/tomcsvan/simulate/engine_predefined/engine_custom"
    : "/home/t/tomcsvan/simulate/engine_predefined/engine_predef";

if ($type === 'custom') {
    $stmt = oci_parse($conn, "SELECT custom_prompt FROM CustomStrategy WHERE strategy_id = :strat_id");
    oci_bind_by_name($stmt, ':strat_id', $strategy_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    $prompt = is_object($row['CUSTOM_PROMPT']) ? $row['CUSTOM_PROMPT']->load() : $row['CUSTOM_PROMPT'];

    $prompt_path = "/tmp/prompt_{$backtest_id}.txt";
    file_put_contents($prompt_path, $prompt);

    $cmd = "$engine_path $history_path $current_path $initial_capital $prompt_path > /tmp/results_{$backtest_id}.txt";
} else {
    $cmd = "$engine_path $history_path $current_path $initial_capital $strategy_id > /tmp/results_{$backtest_id}.txt";
}

exec($cmd);

// Parse engine output
$output_path = "/tmp/results_{$backtest_id}.txt";
if (!file_exists($output_path)) {
    die("Engine did not produce results.");
}
$content = file($output_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$content) {
    die("Output file was empty.");
}

$lines = array_slice($content, 1);
$last_line = end($lines);
$is_holding = str_contains($last_line, 'holdings');
if ($is_holding) array_pop($lines);

// Insert trades
foreach ($lines as $line) {
    [$time, $action, $price, $qty] = str_getcsv($line);

    $time = preg_replace('/:\d{2}Z$/', 'Z', $time);
    $action = strtoupper($action);
    $trade_id = 'T' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 11);

    $stmt = oci_parse($conn, "
        INSERT INTO Trade (trade_id, backtest_id, trade_time, side, price, quantity)
        VALUES (:tid, :bid, TO_TIMESTAMP(:time, 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"'), :act, :p, :q)
    ");
    oci_bind_by_name($stmt, ':tid', $trade_id);
    oci_bind_by_name($stmt, ':bid', $backtest_id);
    oci_bind_by_name($stmt, ':time', $time);
    oci_bind_by_name($stmt, ':act', $action);
    oci_bind_by_name($stmt, ':p', $price);
    oci_bind_by_name($stmt, ':q', $qty);
    oci_execute($stmt); 
}

// Insert holding if present
if ($is_holding) {
    [, , $avg_price, $quantity] = str_getcsv($last_line);

    $stmt = oci_parse($conn, "SELECT 1 FROM HoldingSummary WHERE backtest_id = :bid");
    oci_bind_by_name($stmt, ':bid', $backtest_id);
    oci_execute($stmt);

    if (!oci_fetch($stmt)) {
        $stmt = oci_parse($conn, "
            INSERT INTO HoldingSummary (backtest_id, average_price, quantity)
            VALUES (:bid, :avg, :qty)
        ");
        oci_bind_by_name($stmt, ':bid', $backtest_id);
        oci_bind_by_name($stmt, ':avg', $avg_price);
        oci_bind_by_name($stmt, ':qty', $quantity);
        oci_execute($stmt);
    }
}

oci_commit($conn);

// Clean up
@unlink($history_path);
@unlink($current_path);
@unlink($output_path);
if ($prompt_path) @unlink($prompt_path);

// Redirect to report
header("Location: /~tomcsvan/run_report.php?backtest_id=$backtest_id");
exit();
?>
