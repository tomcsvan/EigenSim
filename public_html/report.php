<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'common/auth.php';
include 'common/header.php';
include 'common/db.php';

$user_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'date';
$only_custom = isset($_GET['only_custom']);
$only_featured = isset($_GET['only_featured']);
$division = isset($_GET['division']);
?>

<div style="padding: 40px;">
  <h2 style="margin-bottom: 20px;">Backtest Reports</h2>

  <!-- Search + Filter + Sort Form -->
  <form method="get" id="filter-form" style="display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap; align-items: center;">
    <input
      type="text"
      name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Search by ticker"
      style="padding: 8px 12px; background: #2a2a2a; color: #e0e0e0; border: none; border-radius: 4px; width: 240px;"
    />

    <select name="sort" onchange="document.getElementById('filter-form').submit();" style="padding: 8px 12px; background: #2a2a2a; color: #e0e0e0; border: none; border-radius: 4px;">
      <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Sort by Date</option>
      <option value="winrate" <?= $sort === 'winrate' ? 'selected' : '' ?>>Sort by Win Rate</option>
    </select>

    <label style="display: flex; align-items: center; gap: 6px; background: #2a2a2a; padding: 6px 12px; border-radius: 4px; border: 1px solid #444; color: #e0e0e0;">
      <input type="checkbox" name="only_custom" onchange="document.getElementById('filter-form').submit();" <?= $only_custom ? 'checked' : '' ?> style="accent-color: #007acc;" />
      Custom
    </label>

    <label style="display: flex; align-items: center; gap: 6px; background: #2a2a2a; padding: 6px 12px; border-radius: 4px; border: 1px solid #444; color: #e0e0e0;">
      <input type="checkbox" name="only_featured" onchange="document.getElementById('filter-form').submit();" <?= $only_featured ? 'checked' : '' ?> style="accent-color: #007acc;" />
      Featured
    </label>

    <label style="display: flex; align-items: center; gap: 6px; background: #2a2a2a; padding: 6px 12px; border-radius: 4px; border: 1px solid #444; color: #e0e0e0;">
      <input type="checkbox" name="division" onchange="document.getElementById('filter-form').submit();" <?= $division ? 'checked' : '' ?> style="accent-color: #007acc;" />
      Show Only Strategies Used on All My Tickers
    </label>

    <button type="submit" class="tab-btn">Apply</button>
  </form>

  <!-- Report Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
    <?php
    $query = "
      SELECT r.backtest_id, b.start_date, b.ticker_symbol,
             COALESCE(p.name, c.name, 'Unnamed Strategy') AS strategy_name,
             s.win_rate, h.quantity AS holdings, st.strategy_type
      FROM Backtest b
      JOIN ReportMetadata r ON b.backtest_id = r.backtest_id
      JOIN ReportStats s ON r.report_id = s.report_id
      JOIN Strategy st ON b.strategy_id = st.strategy_id
      LEFT JOIN PredefinedStrategy p ON st.strategy_id = p.strategy_id
      LEFT JOIN CustomStrategy c ON st.strategy_id = c.strategy_id
      LEFT JOIN HoldingSummary h ON b.backtest_id = h.backtest_id
      WHERE b.user_id = :username
    ";

    if (!empty($search)) {
      $query .= " AND LOWER(b.ticker_symbol) LIKE '%' || LOWER(:search) || '%'";
    }

    if ($only_custom) {
      $query .= " AND st.strategy_type = 'custom'";
    }

    if ($only_featured) {
      $query .= " AND st.strategy_type = 'predefined'";
    }

    if ($division) {
      $query .= "
        AND st.strategy_id IN (
            SELECT strategy_id
            FROM Backtest
            WHERE user_id = :username
            GROUP BY strategy_id
            HAVING COUNT(DISTINCT ticker_symbol) = (
                SELECT COUNT(DISTINCT ticker_symbol)
                FROM Backtest
                WHERE user_id = :username
            )
        )
      ";
    }

    $query .= ($sort === 'winrate') ? " ORDER BY s.win_rate DESC" : " ORDER BY b.start_date DESC";

    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ':username', $user_id);
    if (!empty($search)) {
      oci_bind_by_name($stmt, ':search', $search);
    }
    oci_execute($stmt);

    $row_found = false;
    while ($row = oci_fetch_assoc($stmt)):
      $row_found = true;
    ?>
    <a href="view_results.php?backtest_id=<?= htmlspecialchars($row['BACKTEST_ID']) ?>" style="text-decoration: none; color: inherit;">
        <div class="card" style="background-color: #2a2a2a; padding: 20px; border-radius: 6px; border: 1px solid #444;">
            <h3 style="margin-bottom: 10px;">$<?= htmlspecialchars($row['TICKER_SYMBOL']) ?></h3>
            <p><strong>Strategy:</strong> <?= htmlspecialchars($row['STRATEGY_NAME']) ?></p>
            <p><strong>Type:</strong> <?= ucfirst($row['STRATEGY_TYPE']) ?></p>
            <p><strong>Date:</strong> <?= date('Y-m-d', strtotime($row['START_DATE'])) ?></p>
            <p><strong>Win Rate:</strong> <?= round($row['WIN_RATE'] * 100, 2) ?>%</p>
            <p><strong>Holdings:</strong> <?= (int) $row['HOLDINGS'] ?></p>
        </div>
    </a>
    <?php endwhile; ?>

    <?php if (!$row_found): ?>
      <p style="grid-column: 1 / -1; color: #aaa;">No backtest reports match your filters.</p>
    <?php endif; ?>
  </div>
</div>

<?php include 'common/footer.php'; ?>
