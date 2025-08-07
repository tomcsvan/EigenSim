<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'common/auth.php';
include 'common/header.php';
include 'common/db.php';

$user_id = $_SESSION['user_id'];
$ticker = $_GET['ticker'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;


if (!$ticker || !$start_date || !$end_date || !$user_id) {
    echo "<p style='color: red;'>Missing ticker/date info or user not logged in.</p>";
    exit();
}
?>

<div style="padding: 40px; display: flex; justify-content: center;">
    <div style="max-width: 1200px; width: 100%; display: flex; gap: 40px;">

        <!-- Predefined Strategies -->
        <div style="flex: 1;">
            <h2 style="margin-bottom: 20px;">Featured Strategies</h2>
            <?php
            $stmt = oci_parse($conn, "SELECT strategy_id, name, description FROM PredefinedStrategy");
            if (oci_execute($stmt)) {
                while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS)) !== false) {
                    $sid = htmlspecialchars($row['STRATEGY_ID']);
                    $name = htmlspecialchars($row['NAME']);
                    $desc = nl2br(htmlspecialchars($row['DESCRIPTION']));

                    echo <<<HTML
                    <form method="GET" action="confirm_backtest.php" class="strategy-form">
                        <input type="hidden" name="strategy_id" value="$sid">
                        <input type="hidden" name="ticker" value="$ticker">
                        <input type="hidden" name="start_date" value="$start_date">
                        <input type="hidden" name="end_date" value="$end_date">
                        <div class="card strategy-card">
                            <h3>$name</h3>
                            <p>$desc</p>
                        </div>
                    </form>
                    HTML;
                }
            } else {
                echo "<p style='color: red;'>Failed to load featured strategies.</p>";
            }
            ?>
        </div>

        <!-- Custom Strategies -->
        <div style="flex: 1;">
            <h2 style="margin-bottom: 20px;">Your Custom Strategies</h2>
            <?php
            $stmt = oci_parse($conn, "SELECT strategy_id, name, description FROM CustomStrategy WHERE user_id = :username");
            oci_bind_by_name($stmt, ':username', $user_id);

            if (oci_execute($stmt)) {
                $found = false;
                while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS)) !== false) {
                    $found = true;
                    $sid = htmlspecialchars($row['STRATEGY_ID']);
                    $name = htmlspecialchars($row['NAME']);
                    $desc = nl2br(htmlspecialchars($row['DESCRIPTION']));

                    echo <<<HTML
                    <form method="GET" action="confirm_backtest.php" class="strategy-form">
                        <input type="hidden" name="strategy_id" value="$sid">
                        <input type="hidden" name="ticker" value="$ticker">
                        <input type="hidden" name="start_date" value="$start_date">
                        <input type="hidden" name="end_date" value="$end_date">
                        <div class="card strategy-card">
                            <h3>$name</h3>
                            <p>$desc</p>
                        </div>
                    </form>
                    HTML;
                }

                if (!$found) {
                    echo "<p style='color: #888;'>You have no custom strategies yet.</p>";
                }
            } else {
                echo "<p style='color: red;'>Failed to load custom strategies.</p>";
            }
            ?>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.strategy-form').forEach(form => {
        form.querySelector('.strategy-card').addEventListener('click', () => {
            form.submit();
        });
    });
</script>

<?php include 'common/footer.php'; ?>
