<?php
include 'common/auth.php';
include 'common/header.php';
include 'common/db.php';
include 'common/polygon_api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ticker = strtoupper(trim($_POST['ticker']));
    $user_start = $_POST['start_date'];
    $start = $_POST['start_date'];
    $sstart = date('Y-m-d', strtotime($user_start . ' -1 day'));
    $condition = date('Y-m-d', strtotime($sstart . ' +35 day'));
    $end = $_POST['end_date'];
    $user_id = $_SESSION['user_id'];

    if (empty($ticker)) {
        $error = "Please enter a ticker symbol.";
    }

    if ($sstart >= $end) {
        $error = "Choose start date earlier than end date.";
    }
    
    if ($condition < $end) {
        $error = "Reduce your date range.";
    }

    if (!isset($error)) {
        $polygon_url = "https://api.polygon.io/v2/aggs/ticker/{$ticker}/range/1/minute/{$sstart}/{$end}?adjusted=true&sort=asc&limit=50000&apiKey={$polygon_api_key}";
        $json = file_get_contents($polygon_url);
        $data = json_decode($json, true);

        if (isset($data['status']) && $data['status'] === 'ERROR') {
            $error = "Polygon error: " . $data['error'];
        } elseif (!isset($data['results']) || count($data['results']) === 0) {
            $error = "No data available for this ticker and date range.";
        } else {
            $stmt = oci_parse($conn,"
                MERGE INTO Ticker t
                USING dual
                ON (t.ticker_symbol = :sym)
                WHEN NOT MATCHED THEN
                INSERT (ticker_symbol) VALUES (:sym)");
            oci_bind_by_name($stmt, ':sym', $ticker);
            oci_execute($stmt);
            
            $insertStmt = oci_parse($conn, "
                MERGE INTO PriceHistory ph
                USING dual
                ON (ph.ticker_symbol = :ticker AND ph.trade_time = TO_TIMESTAMP(:ts_str, 'YYYY-MM-DD HH24:MI:SS'))
                WHEN NOT MATCHED THEN
                INSERT (ticker_symbol, trade_time, open_price, high_price, low_price, close_price, volume)
                VALUES (:ticker, TO_TIMESTAMP(:ts_str, 'YYYY-MM-DD HH24:MI:SS'), :open, :high, :low, :close, :volume)");

            foreach ($data['results'] as $candle) {
                $timestamp_ms = $candle['t'];
                $open = $candle['o'];
                $high = $candle['h'];
                $low = $candle['l'];
                $close = $candle['c'];
                $volume = $candle['v'];

                $ts_str = date('Y-m-d H:i:s', intval($timestamp_ms / 1000));

                oci_bind_by_name($insertStmt, ':ticker', $ticker);
                oci_bind_by_name($insertStmt, ':ts_str', $ts_str);
                oci_bind_by_name($insertStmt, ':open', $open);
                oci_bind_by_name($insertStmt, ':high', $high);
                oci_bind_by_name($insertStmt, ':low', $low);
                oci_bind_by_name($insertStmt, ':close', $close);
                oci_bind_by_name($insertStmt, ':volume', $volume);
                oci_execute($insertStmt);
            }

            oci_commit($conn);

            $qs = http_build_query([
                'ticker' => $ticker,
                'start_date' => $start,
                'end_date' => $end
            ]);

            header("Location: backtest_select_strategy.php?$qs");
            exit();
        }
    }
}
?>

<div style="display: flex; justify-content: center; align-items: center; flex-direction: column; padding: 40px;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 30px; background-color: #2a2a2a; border-radius: 10px; box-shadow: 0 0 10px #000;">
        <h2 style="margin-bottom: 20px; text-align: center;">Ticker and Date</h2>

        <?php if (isset($error)): ?>
        <div style="color: #ff7070; margin-bottom: 15px;"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form-card" style="display: flex; flex-direction: column; gap: 16px;">
            <label for="ticker">Ticker Symbol:</label>
            <input type="text" name="ticker" id="ticker" required maxlength="10"
                placeholder="e.g. AAPL"
                style="padding: 8px; font-size: 16px; border-radius: 4px; border: none; background-color: #1e1e1e; color: #eee;">

            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" required
                style="padding: 8px; font-size: 16px; border-radius: 4px; border: none; background-color: #1e1e1e; color: #eee;">

            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" required
                style="padding: 8px; font-size: 16px; border-radius: 4px; border: none; background-color: #1e1e1e; color: #eee;">

            <button type="submit" class="btn"
                style="padding: 10px; font-size: 16px; background-color: #4caf50; color: white; border: none; border-radius: 4px;">
                Next
            </button>
        </form>
    </div>

    <div class="card" style="width: 100%; max-width: 450px; margin-top: 40px; padding: 20px; background-color: #2a2a2a; border-radius: 10px; box-shadow: 0 0 10px #000;">
        <h3 style="margin-bottom: 10px; text-align: center;">Recent Tickers</h3>
        <ul style="list-style: none; padding-left: 0; text-align: center;">
        <?php
        $stmt = oci_parse($conn, "
            SELECT DISTINCT ticker_symbol, TO_CHAR(start_date, 'YYYY-MM-DD') AS start_date, TO_CHAR(end_date, 'YYYY-MM-DD') AS end_date
            FROM Backtest
            WHERE user_id = :username
            ORDER BY start_date DESC FETCH FIRST 10 ROWS ONLY");
        oci_bind_by_name($stmt, ':username', $_SESSION['user_id']);
        oci_execute($stmt);

        $found = false;
        while ($row = oci_fetch_assoc($stmt)) {
            $found = true;
            $ticker = htmlspecialchars($row['TICKER_SYMBOL']);
            $start = $row['START_DATE'];
            $end = $row['END_DATE'];

            $qs = http_build_query([
                'ticker' => $ticker,
                'start_date' => $start,
                'end_date' => $end
            ]);

            echo <<<HTML
            <li style="margin: 10px 0;">
                <a href="backtest_select_strategy.php?$qs"
                onclick="return confirm('Load backtest for $ticker from $start to $end?')"
                style="display: block; padding: 12px 16px; background-color: #1f1f1f; border-radius: 6px; box-shadow: 0 0 6px #00000066; text-decoration: none;">
                    <strong style="color: #4caf50;">$$ticker</strong><br>
                    <span style="color: #bbb;">$start to $end</span>
                </a>
            </li>
            HTML;
        }

        if (!$found) {
            echo "<li style='color: #777;'>No recent tickers.</li>";
        }
        ?>
        </ul>
    </div>
</div>
