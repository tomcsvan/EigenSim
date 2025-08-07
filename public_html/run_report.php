<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/auth.php';
include 'common/db.php';

$backtest_id = $_GET['backtest_id'] ?? null;
if (!$backtest_id) {
    die("Missing backtest ID.");
}

$report_id = strtoupper(bin2hex(random_bytes(10)));

// Fetch trades without ordering by time
$stmt = oci_parse($conn, "
    SELECT TO_CHAR(trade_time, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS trade_time_str,
           side, price, quantity
    FROM Trade
    WHERE backtest_id = :bid
");
oci_bind_by_name($stmt, ':bid', $backtest_id);
oci_execute($stmt);

// Write CSV
$csv_path = "/tmp/trades_{$backtest_id}.csv";
$fp = fopen($csv_path, 'w');
fputcsv($fp, ['time', 'side', 'price', 'quantity']);

$row_count = 0;
while ($row = oci_fetch_assoc($stmt)) {
    $trade_time = $row['TRADE_TIME_STR'] . ":00Z";
    $side = strtoupper($row['SIDE']);
    $price = $row['PRICE'];
    $quantity = $row['QUANTITY'];
    fputcsv($fp, [$trade_time, $side, $price, $quantity]);
    $row_count++;
}
fclose($fp);

if ($row_count === 0) {
    die("No trades found for this backtest.");
}

// Run report engine
$engine_path = "/home/t/tomcsvan/report/main.py";
$command = "python3 $engine_path $csv_path 2>&1";
$output = [];
$return_code = 0;
exec($command, $output, $return_code);

$keys = [
    'total_return', 'annualized_return', 'sharpe_ratio', 'max_drawdown',
    'win_rate', 'trade_count', 't_stat', 'p_value',
    'confidence_95_low', 'confidence_95_high'
];

if (count($output) < count($keys)) {
    die("Output missing values. Got " . count($output) . " lines.");
}

$stats = [];
for ($i = 0; $i < count($keys); $i++) {
    $stats[$keys[$i]] = (float) $output[$i];
}

// Insert ReportMetadata
$stmt = oci_parse($conn, "
    INSERT INTO ReportMetadata (report_id, backtest_id)
    VALUES (:rid, :bid)
");
oci_bind_by_name($stmt, ':rid', $report_id);
oci_bind_by_name($stmt, ':bid', $backtest_id);
oci_execute($stmt);

// Insert ReportStats
$stmt = oci_parse($conn, "
    INSERT INTO ReportStats (
        report_id, generated_at, total_return, annualized_return,
        sharpe_ratio, max_drawdown, win_rate, trade_count,
        t_stat, p_value, confidence_95_low, confidence_95_high
    ) VALUES (
        :rid, SYSTIMESTAMP, :ret, :ann, :sharpe, :dd, :win,
        :count, :tstat, :pval, :ci_low, :ci_high
    )
");

oci_bind_by_name($stmt, ':rid', $report_id);
oci_bind_by_name($stmt, ':ret', $stats['total_return']);
oci_bind_by_name($stmt, ':ann', $stats['annualized_return']);
oci_bind_by_name($stmt, ':sharpe', $stats['sharpe_ratio']);
oci_bind_by_name($stmt, ':dd', $stats['max_drawdown']);
oci_bind_by_name($stmt, ':win', $stats['win_rate']);
oci_bind_by_name($stmt, ':count', $stats['trade_count']);
oci_bind_by_name($stmt, ':tstat', $stats['t_stat']);
oci_bind_by_name($stmt, ':pval', $stats['p_value']);
oci_bind_by_name($stmt, ':ci_low', $stats['confidence_95_low']);
oci_bind_by_name($stmt, ':ci_high', $stats['confidence_95_high']);
oci_execute($stmt);

oci_commit($conn);

@unlink($csv_path);

header("Location: /~tomcsvan/view_results.php?backtest_id=$backtest_id");
exit();
?>
