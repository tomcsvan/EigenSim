<?php include 'common/auth.php'; ?>
<?php include 'common/header.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/db.php';

$user_id = $_SESSION['user_id'];

// Total custom strategies
$strategy_stmt = oci_parse($conn, "SELECT COUNT(*) FROM CustomStrategy WHERE user_id = :username");
oci_bind_by_name($strategy_stmt, ":username", $user_id);
oci_execute($strategy_stmt);
oci_fetch($strategy_stmt);
$total_strategies = oci_result($strategy_stmt, 1);

// Total simulations run
$backtest_stmt = oci_parse($conn, "SELECT COUNT(*) FROM Backtest WHERE user_id = :username");
oci_bind_by_name($backtest_stmt, ":username", $user_id);
oci_execute($backtest_stmt);
oci_fetch($backtest_stmt);
$total_simulations = oci_result($backtest_stmt, 1);

// Stub: Live deployments
$live_deployments = 0;

// Average winrate
$win_stmt = oci_parse($conn, "
    SELECT AVG(RS.win_rate) AS avg_win_rate
    FROM ReportStats RS
    JOIN ReportMetadata RM ON RS.report_id = RM.report_id
    JOIN Backtest B ON RM.backtest_id = B.backtest_id
    WHERE B.user_id = :username
");
oci_bind_by_name($win_stmt, ":username", $user_id);
oci_execute($win_stmt);
$row = oci_fetch_assoc($win_stmt);
$average_win_rate = ($row && isset($row['AVG_WIN_RATE']))
    ? round($row['AVG_WIN_RATE'] * 100, 2) . '%'
    : 'N/A';

$activity = [];

// Recent report activity
$report_act = oci_parse($conn, "
    SELECT TO_CHAR(RS.generated_at, 'YYYY-MM-DD') AS generated_at,
           S.strategy_type,
           CS.name AS custom_name,
           PS.name AS predefined_name,
           B.ticker_symbol
    FROM ReportStats RS
    JOIN ReportMetadata RM ON RS.report_id = RM.report_id
    JOIN Backtest B ON RM.backtest_id = B.backtest_id
    JOIN Strategy S ON B.strategy_id = S.strategy_id
    LEFT JOIN CustomStrategy CS ON S.strategy_id = CS.strategy_id
    LEFT JOIN PredefinedStrategy PS ON S.strategy_id = PS.strategy_id
    WHERE B.user_id = :username
    ORDER BY RS.generated_at DESC FETCH FIRST 10 ROWS ONLY
");
oci_bind_by_name($report_act, ":username", $user_id);
oci_execute($report_act);

while ($row = oci_fetch_array($report_act, OCI_ASSOC + OCI_RETURN_LOBS)) {
    $date = $row['GENERATED_AT'];
    $type = ucfirst(strtolower($row['STRATEGY_TYPE']));
    $name = $row['CUSTOM_NAME'] ?? $row['PREDEFINED_NAME'] ?? 'Unnamed Strategy';
    $ticker = strtoupper($row['TICKER_SYMBOL'] ?? 'UNKNOWN');

    $activity[] = [
        'date' => $date,
        'desc' => "Ran $type strategy “" . htmlspecialchars($name) . "” on $ticker"
    ];
}

// Strategy creation events
$create_stmt = oci_parse($conn, "
    SELECT TO_CHAR(created_date, 'YYYY-MM-DD') AS created_date, name
    FROM CustomStrategy
    WHERE user_id = :username
    ORDER BY created_date DESC FETCH FIRST 10 ROWS ONLY
");
oci_bind_by_name($create_stmt, ':username', $user_id);
oci_execute($create_stmt);

while ($row = oci_fetch_array($create_stmt, OCI_ASSOC)) {
    $date = $row['CREATED_DATE'];
    $name = htmlspecialchars(trim($row['NAME'] ?? ''));

    if ($name === '') $name = '(Unnamed)';

    $activity[] = [
        'date' => $date,
        'desc' => "Created strategy $name"
    ];
}
?>

<div class="dashboard-container">
    <h2 style="text-align:center; font-size:1.8rem; color:#00cccc; margin-bottom:1rem;">Dashboard</h2>
    <p class="subheading">Welcome back, <b><?= htmlspecialchars($_SESSION['user_id']) ?></b>. Here's a snapshot of your activity.</p>

    <!-- Summary Metrics -->
    <div class="dashboard-summary">
        <div class="summary-box">
            <div class="metric-title">Total Strategies</div>
            <div class="metric-value"><?= $total_strategies ?></div>
        </div>
        <div class="summary-box">
            <div class="metric-title">Simulations Run</div>
            <div class="metric-value"><?= $total_simulations ?></div>
        </div>
        <div class="summary-box">
            <div class="metric-title">Live Deployments</div>
            <div class="metric-value"><?= $live_deployments ?></div>
        </div>
        <div class="summary-box">
            <div class="metric-title">Average Win Rate</div>
            <div class="metric-value"><?= $average_win_rate ?></div>
        </div>
    </div>

    <!-- Functional Modules -->
    <div class="dashboard-grid">
        <div class="card">
            <h3>Your Strategies</h3>
            <p>Create, update, and manage your algorithmic strategies.</p>
            <a href="create_strategy.php">Manage Strategies →</a>
        </div>

        <div class="card">
            <h3>Backtest Simulator</h3>
            <p>Run simulations on historical market data to validate your strategy.</p>
            <a href="backtest_select_ticker.php">Launch Simulator →</a>
        </div>

        <div class="card">
            <h3>Performance Reports</h3>
            <p>Analyze backtest outcomes and review key performance indicators.</p>
            <a href="report.php">View Reports →</a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="dashboard-activity">
        <h3>Recent Activity</h3>
        <ul>
            <?php foreach ($activity as $event): ?>
                <li>[<?= $event['date'] ?>] <?= $event['desc'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include 'common/footer.php'; ?>
