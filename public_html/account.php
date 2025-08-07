<?php include 'common/auth.php'; ?>
<?php include 'common/header.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/db.php';

$section = $_GET['section'] ?? 'summary';
$user_id = $_SESSION['user_id'];

$stats = [
    'projects' => 0,
    'backtests' => 0,
    'live_volume' => 0,
    'public_algorithms' => 0,
    'live_deployments' => 0,
    'lines_of_code' => 0
];

$stmt = oci_parse($conn, "SELECT COUNT(*) AS total FROM Backtest WHERE user_id = :username");
oci_bind_by_name($stmt, ':username', $user_id);
oci_execute($stmt);
if ($row = oci_fetch_assoc($stmt)) {
    $stats['backtests'] = (int) $row['TOTAL'];
}

$stmt = oci_parse($conn, "SELECT COUNT(*) AS total FROM CustomStrategy WHERE user_id = :username");
oci_bind_by_name($stmt, ':username', $user_id);
oci_execute($stmt);
if ($row = oci_fetch_assoc($stmt)) {
    $stats['public_algorithms'] = (int) $row['TOTAL'];
}

$stmt = oci_parse($conn, "SELECT custom_prompt FROM CustomStrategy WHERE user_id = :username");
oci_bind_by_name($stmt, ':username', $user_id);
oci_execute($stmt);
$totalLines = 0;
while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS)) !== false) {
    $prompt = $row['CUSTOM_PROMPT'] ?? '';
    $totalLines += substr_count(trim($prompt), "\n") + 1;
}
$stats['lines_of_code'] = $totalLines;
?>

<div class="account-layout">
  <!-- stratidebar Navigation -->
  <aside class="account-sidebar">
    <ul>
      <li><a href="?section=summary" class="<?= $section === 'summary' ? 'active' : '' ?>">Summary</a></li>
      <li><a href="?section=strategies" class="<?= $section === 'strategies' ? 'active' : '' ?>">Strategies</a></li>
      <li><a href="?section=settings" class="<?= $section === 'settings' ? 'active' : '' ?>">Account Settings</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="account-content">
    <?php if ($section === 'summary'): ?>
      <h2>Account Overview</h2>
      <hr>
      <div class="summary-card">
        <div class="summary-grid">
          <div class="summary-item"><h4><?= $stats['projects'] ?></h4><p>Projects</p></div>
          <div class="summary-item"><h4><?= $stats['backtests'] ?></h4><p>Backtests</p></div>
          <div class="summary-item"><h4><?= $stats['live_volume'] ?></h4><p>Live Volume</p></div>
          <div class="summary-item"><h4><?= $stats['public_algorithms'] ?></h4><p>Public Algorithms</p></div>
          <div class="summary-item"><h4><?= $stats['live_deployments'] ?></h4><p>Live Deployments</p></div>
          <div class="summary-item"><h4><?= $stats['lines_of_code'] ?></h4><p>Lines of Code</p></div>
        </div>
      </div>

    <?php elseif ($section === 'strategies'):?>
        <h2>Your Strategies</h2>
        <div class="tab-container">
            <div class="tab-buttons">
            <button class="tab-btn active" data-tab="custom-tab">Custom Strategies</button>
            <button class="tab-btn" data-tab="predefined-tab">Featured Strategies</button>
            </div>

            <div id="custom-tab" class="tab-content active">
            <h3>Custom Strategies</h3>
            <div class="strategy-list">
                <?php
                $stmt = oci_parse($conn, "
                SELECT strategy_id, name, description, created_Date
                FROM CustomStrategy
                WHERE user_id = :username");
                oci_bind_by_name($stmt, ':username', $user_id);
                oci_execute($stmt);
                $hasResults = false;
                while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS)) !== false) {
                    $hasResults = true;
                    $id = htmlspecialchars($row['STRATEGY_ID']);
                    $name = htmlspecialchars($row['NAME']);
                    $desc = nl2br(htmlspecialchars($row['DESCRIPTION']));
                    $created = htmlspecialchars($row['CREATED_DATE']);
                
                    echo <<<HTML
                    <div class="strategy-card" onclick="submitDelete('$id')" style="cursor: pointer;">
                        <h4>$name</h4>
                        <p>$desc</p>
                        <small>Created: $created</small>
                    </div>
                    HTML;
                }
                echo <<<HTML
                    <form id="deleteForm" method="POST" action="delete_strategy.php" style="display:none;">
                        <input type="hidden" name="strategy_id" id="deleteInput">
                    </form>

                    HTML;
                if (!$hasResults) {
                    echo '<p>No custom strategies found.</p>';
                }
                ?>
            </div>
            </div>

            <div id="predefined-tab" class="tab-content">
            <h3>Featured Strategies</h3>
            <div class="strategy-list">
                <?php
                $query = "
                SELECT strategy_id, name, description, created_date, logic
                FROM PredefinedStrategy";
                $stmt = oci_parse($conn, $query);
                oci_execute($stmt);
                $hasResults = false;
                while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS)) !== false) {
                    $hasResults = true;
                    echo '<div class="strategy-card">';
                    echo '<h4>' . htmlspecialchars($row['NAME']) . '</h4>';
                    echo '<p><strong>Description:</strong> ' . nl2br(htmlspecialchars($row['DESCRIPTION'])) . '</p>';
                    echo '<p><strong>Logic:</strong> ' . nl2br(htmlspecialchars($row['LOGIC'])) . '</p>';
                    echo '<p><strong>Created:</strong> ' . htmlspecialchars($row['CREATED_DATE']) . '</p>';

                    $paramStmt = oci_parse($conn, "
                        SELECT pt.name AS param_name, pv.assigned_value
                        FROM ParameterValue pv
                        JOIN Parameter p ON pv.parameter_id = p.parameter_id
                        JOIN ParameterType pt ON p.name = pt.name
                        WHERE pv.strategy_id = :stratid
                    ");
                    oci_bind_by_name($paramStmt, ':stratid', $row['STRATEGY_ID']);
                    oci_execute($paramStmt);

                    $paramList = [];
                    while (($p = oci_fetch_assoc($paramStmt)) !== false) {
                        $paramList[] = htmlspecialchars($p['PARAM_NAME']) . ': ' . htmlspecialchars($p['ASSIGNED_VALUE']);
                    }

                    if (!empty($paramList)) {
                        echo '<p><strong>Parameters:</strong><br>' . implode('<br>', $paramList) . '</p>';
                    }

                    echo '</div>';
                }
                if (!$hasResults) {
                    echo '<p>No predefined strategies available.</p>';
                }
                ?>
            </div>
            </div>
        </div>

    <script>
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
        const tab = button.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(tab).classList.add('active');
        });
    });
    </script>
    <script>
    function submitDelete(id) {
        if (confirm('Are you sure you want to delete this strategy?')) {
        document.getElementById('deleteInput').value = id;
        document.getElementById('deleteForm').submit();
        }
    }
    </script>

    <?php elseif ($section === 'settings'):
      $stmt = oci_parse($conn, "SELECT first_name, last_name FROM Users WHERE user_id = :username");
      oci_bind_by_name($stmt, ':username', $user_id);
      oci_execute($stmt);
      $user = oci_fetch_assoc($stmt);
    ?>
    <h2>Account Settings</h2>
    <div class="settings-card">
        <div id="account-display">
            <div class="setting-row"><span class="label">First Name</span><span><?= htmlspecialchars($user['FIRST_NAME']) ?></span></div>
            <div class="setting-row"><span class="label">Last Name</span><span><?= htmlspecialchars($user['LAST_NAME']) ?></span></div>
            <button id="edit-button" class="btn-edit">Edit Profile</button>
        </div>

        <form id="account-form" method="POST" action="update_account.php" style="display: none;">
            <div class="setting-row">
            <label for="firstname">First Name</label>
            <input type="text" name="firstname" id="firstname" value="<?= htmlspecialchars($user['FIRST_NAME']) ?>" required>
            </div>

            <div class="setting-row">
            <label for="lastname">Last Name</label>
            <input type="text" name="lastname" id="lastname" value="<?= htmlspecialchars($user['LAST_NAME']) ?>" required>
            </div>

            <div class="setting-row">
            <label for="password">New Password <small>(leave blank to keep current)</small></label>
            <input type="password" name="password" id="password">
            </div>

            <div class="setting-row">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password">
            </div>

            <div class="form-buttons">
            <button type="button" id="cancel-button">Cancel</button>
            <button type="submit">Save Changes</button>
            </div>
        </form>
    </div>


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const display = document.getElementById("account-display");
        const form = document.getElementById("account-form");
        const editBtn = document.getElementById("edit-button");
        const cancelBtn = document.getElementById("cancel-button");

        editBtn.addEventListener("click", function () {
        display.style.display = "none";
        form.style.display = "grid";
        });

        cancelBtn.addEventListener("click", function () {
        form.style.display = "none";
        display.style.display = "block";
        });
    });
    </script>
    <?php endif; ?>
  </main>
</div>

<?php include 'common/footer.php'; ?>
