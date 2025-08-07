<?php
include 'common/auth.php';
include 'common/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();
include 'common/db.php';

$user_id = $_SESSION['user_id'];
$strategy_id = $_GET['strategy_id'] ?? null;
$ticker = $_GET['ticker'] ?? null;
$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;

if (!$strategy_id || !$ticker || !$start || !$end || !$user_id) {
    echo "<p style='color: red;'>Missing data for backtest confirmation.</p>";
    exit();
}

// Generate a new backtest_id
function generateId($length = 12) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}
$backtest_id = generateId();

// Insert into Backtest
$stmt = oci_parse($conn, "
    INSERT INTO Backtest (backtest_id, strategy_id, user_id, ticker_symbol, start_date, end_date)
    VALUES (:bid, :strat_id, :username, :ticker, TO_DATE(:startdt, 'YYYY-MM-DD'), TO_DATE(:enddt, 'YYYY-MM-DD'))
");
oci_bind_by_name($stmt, ':bid', $backtest_id);
oci_bind_by_name($stmt, ':strat_id', $strategy_id);
oci_bind_by_name($stmt, ':username', $user_id);
oci_bind_by_name($stmt, ':ticker', $ticker);
oci_bind_by_name($stmt, ':startdt', $start);
oci_bind_by_name($stmt, ':enddt', $end);
oci_execute($stmt);
oci_commit($conn);

// Get strategy type
$stmt = oci_parse($conn, "
    SELECT strategy_type FROM Strategy WHERE strategy_id = :strat_id
");
oci_bind_by_name($stmt, ':strat_id', $strategy_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$type = strtolower($row['STRATEGY_TYPE'] ?? '');

// Fetch strategy details
if ($type === 'custom') {
    $stmt2 = oci_parse($conn, "SELECT name, description FROM CustomStrategy WHERE strategy_id = :strat_id");
} else {
    $stmt2 = oci_parse($conn, "SELECT name, description FROM PredefinedStrategy WHERE strategy_id = :strat_id");
}
oci_bind_by_name($stmt2, ':strat_id', $strategy_id);
oci_execute($stmt2);
$strat = oci_fetch_assoc($stmt2);

$strat_name = htmlspecialchars(is_object($strat['NAME']) ? $strat['NAME']->load() : $strat['NAME']);
$strat_desc_raw = is_object($strat['DESCRIPTION']) ? $strat['DESCRIPTION']->load() : $strat['DESCRIPTION'];
$strat_desc = nl2br(htmlspecialchars($strat_desc_raw));
?>

<div class="backtest-confirm-container">
    <h2 class="section-title">Confirm Backtest Configuration</h2>

    <div class="confirm-content">
        <div class="strategy-info card">
            <p><strong>Ticker:</strong> <?= htmlspecialchars($ticker) ?></p>
            <p><strong>Date Range:</strong> <?= htmlspecialchars($start) ?> to <?= htmlspecialchars($end) ?></p>
            <p><strong>Strategy:</strong> <?= $strat_name ?> (<?= ucfirst($type) ?>)</p>
            <p><strong>Description:</strong><br><?= $strat_desc ?></p>
        </div>

        <form method="POST" action="run_engine.php" class="confirm-form card">
            <input type="hidden" name="backtest_id" value="<?= $backtest_id ?>">
            <input type="hidden" name="strategy_id" value="<?= $strategy_id ?>">

            <label for="initial_capital">Initial Capital ($):</label>
            <input type="text" name="initial_capital" id="initial_capital" required
                   pattern="^\d+(\.\d{1,2})?$"
                   placeholder="e.g. 10000">

            <button type="submit" class="button-primary">Start Backtest</button>
        </form>
    </div>
</div>

<?php include 'common/footer.php'; ?>
