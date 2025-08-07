<?php
include 'common/auth.php';
include 'common/header.php';
include 'common/db.php';

$backtest_id = $_GET['backtest_id'] ?? null;
if (!$backtest_id) {
    die("Missing backtest ID.");
}

// Fetch report_id
$stmt = oci_parse($conn, "
    SELECT report_id FROM ReportMetadata WHERE backtest_id = :backtest_id
");
oci_bind_by_name($stmt, ':backtest_id', $backtest_id);
oci_execute($stmt);

if (!($row = oci_fetch_assoc($stmt))) {
    die("Report not found.");
}
$report_id = $row['REPORT_ID'];

// Fetch report stats
$stmt = oci_parse($conn, "
    SELECT TO_CHAR(generated_at, 'YYYY-MM-DD HH24:MI:SS') AS generated_at,
           total_return, annualized_return, sharpe_ratio, max_drawdown,
           win_rate, trade_count, t_stat, p_value,
           confidence_95_low, confidence_95_high
    FROM ReportStats
    WHERE report_id = :rid
");
oci_bind_by_name($stmt, ':rid', $report_id);
oci_execute($stmt);
$report = oci_fetch_assoc($stmt);

// Fetch backtest info
$stmt = oci_parse($conn, "
    SELECT * FROM Backtest WHERE backtest_id = :backtest_id
");
oci_bind_by_name($stmt, ':backtest_id', $backtest_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

$strategy_id = $row['STRATEGY_ID'];
$ticker = [
    'TICKER_SYMBOL' => $row['TICKER_SYMBOL'],
    'START_DATE'    => date('Y-m-d', strtotime($row['START_DATE'])),
    'END_DATE'      => date('Y-m-d', strtotime($row['END_DATE']))
];

// Fetch strategy name and type
$stmt = oci_parse($conn, "
    SELECT s.strategy_type,
           COALESCE(cs.name, ps.name) AS strategy_name
    FROM Strategy s
    LEFT JOIN CustomStrategy cs ON s.strategy_id = cs.strategy_id
    LEFT JOIN PredefinedStrategy ps ON s.strategy_id = ps.strategy_id
    WHERE s.strategy_id = :sid
");
oci_bind_by_name($stmt, ':sid', $strategy_id);
oci_execute($stmt);

if ($strat = oci_fetch_assoc($stmt)) {
    $ticker['STRATEGY_TYPE'] = $strat['STRATEGY_TYPE'];
    $ticker['STRATEGY_NAME'] = $strat['STRATEGY_NAME'];
}

// Fetch holdings from HoldingSummary
$stmt = oci_parse($conn, "
    SELECT average_price, quantity
    FROM HoldingSummary
    WHERE backtest_id = :bid
");
oci_bind_by_name($stmt, ':bid', $backtest_id);
oci_execute($stmt);
$holding = oci_fetch_assoc($stmt);
?>

<div class="dashboard-container">
    <h2 class="section-title">Performance Report</h2>
    <p class="subheading" style="text-align: center;">
        Generated on: <?php echo $report['GENERATED_AT']; ?>
    </p>

    <div class="summary-card" style="margin: auto;">
        <h3>Backtest Info</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4><?php echo '$' . htmlspecialchars($ticker['TICKER_SYMBOL']); ?></h4>
                <p>Ticker Symbol</p>
            </div>
            <div class="summary-item">
                <h4><?php echo number_format($holding['QUANTITY']); ?></h4>
                <p>Units Held</p>
            </div>
            <div class="summary-item">
                <h4><?php echo htmlspecialchars($ticker['STRATEGY_NAME'] ?? 'Unknown'); ?></h4>
                <p>Strategy Name</p>
            </div>
            <div class="summary-item">
                <h4><?php echo ucfirst(strtolower($ticker['STRATEGY_TYPE'] ?? '')); ?></h4>
                <p>Strategy Type</p>
            </div>
            <div class="summary-item">
                <h4><?php echo $ticker['START_DATE']; ?></h4>
                <p>Start Date</p>
            </div>
            <div class="summary-item">
                <h4><?php echo $ticker['END_DATE']; ?></h4>
                <p>End Date</p>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Metrics Overview</h3>
        <div class="summary-grid" style="grid-template-columns: repeat(2, 1fr);">
            <?php
            $metrics = [
                'Total Return'         => 'TOTAL_RETURN',
                'Annualized Return'    => 'ANNUALIZED_RETURN',
                'Sharpe Ratio'         => 'SHARPE_RATIO',
                'Max Drawdown'         => 'MAX_DRAWDOWN',
                'Win Rate'             => 'WIN_RATE',
                'Trade Count'          => 'TRADE_COUNT',
                'T-Statistic'          => 'T_STAT',
                'P-Value'              => 'P_VALUE',
                '95% Confidence Low'   => 'CONFIDENCE_95_LOW',
                '95% Confidence High'  => 'CONFIDENCE_95_HIGH'
            ];

            foreach ($metrics as $label => $key):
                $value = $report[$key];
                $formatted = is_numeric($value) ? number_format($value, 4) : htmlspecialchars($value);
            ?>
                <div class="summary-item">
                    <h4><?php echo $formatted; ?></h4>
                    <p><?php echo $label; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- Trades  -->
    <?php
    $stmt = oci_parse($conn, "
        WITH sequential_trades AS (
            SELECT 
                t.*,
                ROWNUM AS row_num
            FROM Trade t
            WHERE backtest_id = :backtest_id
        ),
        trade_pairs AS (
            SELECT 
                buy.trade_id as buy_id,
                buy.trade_time as buy_time,
                buy.price as buy_price,
                buy.quantity as quantity,
                sell.trade_id as sell_id,
                sell.trade_time as sell_time,
                sell.price as sell_price,
                (sell.price - buy.price) * buy.quantity as profit
            FROM sequential_trades buy
            JOIN sequential_trades sell ON sell.row_num = buy.row_num + 1
            WHERE MOD(buy.row_num, 2) = 1
        )
        SELECT 
            buy_id,
            TO_CHAR(buy_time, 'YYYY-MM-DD HH24:MI:SS') as buy_time,
            buy_price,
            quantity,
            sell_id,
            TO_CHAR(sell_time, 'YYYY-MM-DD HH24:MI:SS') as sell_time,
            sell_price,
            profit,
            CASE WHEN profit > 0 THEN 1 ELSE 0 END as is_winning
        FROM trade_pairs
        ORDER BY buy_time ASC
    ");
    oci_bind_by_name($stmt, ':backtest_id', $backtest_id);
    oci_execute($stmt);

    $winning_pairs = [];
    $losing_pairs = [];

    while ($row = oci_fetch_assoc($stmt)) {
        $pair = [
            'buy_id' => $row['BUY_ID'],
            'buy_time' => $row['BUY_TIME'],
            'buy_price' => floatval($row['BUY_PRICE']),
            'sell_id' => $row['SELL_ID'],
            'sell_time' => $row['SELL_TIME'],
            'sell_price' => floatval($row['SELL_PRICE']),
            'quantity' => intval($row['QUANTITY']),
            'profit' => floatval($row['PROFIT'])
        ];

        if ($row['IS_WINNING'] == 1) {
            $winning_pairs[] = $pair;
        } else {
            $losing_pairs[] = $pair;
        }
    }
    ?>

    <div class="summary-card" style="margin: auto; margin-top: 30px;">
        <h3>Trade Pairs Analysis</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">

            <!-- Winning Trades -->
            <div>
                <h4 class="winning-trades-header">
                    Winning Trades (<?= count($winning_pairs) ?>)
                </h4>
                <div class="trades-scroll">
                    <?php if (empty($winning_pairs)): ?>
                        <p class="no-trades">No winning trades found</p>
                    <?php else: ?>
                        <?php foreach ($winning_pairs as $pair): ?>
                            <div class="trade-card winning-trade-card">
                                <div class="trade-row">
                                    <span class="buy-label">BUY</span>
                                    <span class="trade-info"><?= $pair['quantity'] ?> shares at $<?= number_format($pair['buy_price'], 2) ?></span>
                                    <span class="trade-timestamp"><?= date('M j, H:i', strtotime($pair['buy_time'])) ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="sell-label">SELL</span>
                                    <span class="trade-info"><?= $pair['quantity'] ?> shares at $<?= number_format($pair['sell_price'], 2) ?></span>
                                    <span class="trade-timestamp"><?= date('M j, H:i', strtotime($pair['sell_time'])) ?></span>
                                </div>
                                <div class="profit-display">
                                    Profit: $<?= number_format($pair['profit'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Losing Trades -->
            <div>
                <h4 class="losing-trades-header">
                    Losing Trades (<?= count($losing_pairs) ?>)
                </h4>
                <div class="trades-scroll">
                    <?php if (empty($losing_pairs)): ?>
                        <p class="no-trades">No losing trades found</p>
                    <?php else: ?>
                        <?php foreach ($losing_pairs as $pair): ?>
                            <div class="trade-card losing-trade-card">
                                <div class="trade-row">
                                    <span class="buy-label">BUY</span>
                                    <span class="trade-info"><?= $pair['quantity'] ?> shares at $<?= number_format($pair['buy_price'], 2) ?></span>
                                    <span class="trade-timestamp"><?= date('M j, H:i', strtotime($pair['buy_time'])) ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="sell-label">SELL</span>
                                    <span class="trade-info"><?= $pair['quantity'] ?> shares at $<?= number_format($pair['sell_price'], 2) ?></span>
                                    <span class="trade-timestamp"><?= date('M j, H:i', strtotime($pair['sell_time'])) ?></span>
                                </div>
                                <div class="loss-display">
                                    Loss: $<?= number_format($pair['profit'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'common/footer.php'; ?>