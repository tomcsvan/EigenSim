<?php
include 'common/auth.php';
include 'common/header.php';
include 'common/db.php';

$user_id = $_SESSION['user_id'];
$message = "";

// Reusable 12-char ID generator
function generateId($prefix = '', $length = 12) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return $prefix . substr(str_shuffle($chars), 0, $length - strlen($prefix));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prompt'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $raw_prompt = trim($_POST['prompt']);

    $binary_path = __DIR__ . '/../validator/prompt_validator.py';
    $escaped_prompt = escapeshellarg($raw_prompt);
    $output = shell_exec("python3 $binary_path $escaped_prompt");

    $is_valid = trim($output) === "OK";

    if ($is_valid && !empty($name) && !empty($raw_prompt)) {
        $strategy_id = generateId('S', 12);

        // Insert into Strategy
        $stmt1 = oci_parse($conn, "INSERT INTO Strategy (strategy_id, strategy_type) VALUES (:id, 'custom')");
        oci_bind_by_name($stmt1, ":id", $strategy_id);
        oci_execute($stmt1);

        // Insert into CustomStrategy
        $stmt2 = oci_parse($conn, "
            INSERT INTO CustomStrategy (strategy_id, user_id, name, description, created_date, custom_prompt) 
            VALUES (:id, :username, :namee, :descr, SYSDATE, :prompt)");
        oci_bind_by_name($stmt2, ":id", $strategy_id);
        oci_bind_by_name($stmt2, ":username", $user_id);
        oci_bind_by_name($stmt2, ":namee", $name);
        oci_bind_by_name($stmt2, ":descr", $description);
        oci_bind_by_name($stmt2, ":prompt", $raw_prompt);
        oci_execute($stmt2);

        oci_commit($conn);
        $message = "Strategy created successfully.";
    } else {
        $message = "Invalid input. Please check your prompt and try again.";
    }
}
?>

<div class="dashboard-container">
    <h2 style="text-align:center; font-size:1.8rem; color:#00cccc; margin-bottom:1rem;">Create New Strategy</h2>

    <div style="display:flex; justify-content:center; gap:1rem; margin-bottom:2rem;">
        <button class="tab-btn" onclick="showTab('instruction')">Instruction</button>
        <button class="tab-btn" onclick="showTab('create')">Create</button>
    </div>

    <div id="instruction" class="tab-section" style="display:block;">
        <div class="form-container">
            <h3>Custom Prompt Syntax</h3>
            <p>Your custom strategy must include both a buy and sell condition, written as two separate lines:</p>
            <div style="margin-left: 1rem; margin-bottom: 1rem;">
                <p><b>Buy when:</b> &lt;buy-condition&gt;</p>
                <p><b>Sell when:</b> &lt;sell-condition&gt;</p>
            </div>
            <hr>
            <h4>Supported Indicators</h4>
            <ul>
                <li><b>price</b>, <b>open</b>, <b>close</b>, <b>high</b>, <b>low</b>, <b>volume</b></li>
                <li><b>SMA(n)</b>, <b>EMA(n)</b>, <b>RSI(n)</b></li>
                <li><b>MACD</b>, <b>signal</b></li>
            </ul>
            <hr>
            <h4>Logical Operators</h4>
            <p>&gt;, &gt;=, &lt;, &lt;=, ==, !=, AND, OR</p>
            <hr>
            <h4>Examples</h4>
            <ul>
                <li><b>Buy when:</b> RSI(14) &lt; 30<br><b>Sell when:</b> RSI(14) &gt; 70</li>
                <li><b>Buy when:</b> price &gt; SMA(20) AND volume &gt; SMA(20)<br><b>Sell when:</b> price &lt; SMA(20)</li>
                <li><b>Buy when:</b> EMA(12) &gt; EMA(26) AND MACD &gt; signal<br><b>Sell when:</b> EMA(12) &lt; EMA(26)</li>
            </ul>
            <hr>
            <h4>Invalid Prompt Examples</h4>
            <ul>
                <li>Missing either "Buy when:" or "Sell when:"</li>
                <li>Using unsupported indicators like <b>Bollinger</b></li>
                <li>Using lowercase keywords like <b>and</b> instead of <b>AND</b></li>
            </ul>
            <hr>
            <h4>Additional Notes</h4>
            <ul>
                <li>All indicator names and keywords are case sensitive.</li>
                <li>No parentheses or nested expressions are supported at this time.</li>
                <li>Keep one condition per line, and keep it simple.</li>
            </ul>
        </div>
    </div>

    <div id="create" class="tab-section" style="display:none;">
        <div class="form-container">
            <h3>Strategy Information</h3>
            <p>This section allows you to create a new strategy by entering a name, description, and custom logic prompt.</p>
            <form method="POST">
                <label for="name" style="display:block; margin-bottom:0.5rem;">Strategy Name:</label>
                <input type="text" name="name" id="name" required
                    style="width:100%; padding:0.5rem; margin-bottom:1.5rem; border-radius:6px; border:none; background-color:#1f1d1d; color:#e0e0e0;">

                <label for="description" style="display:block; margin-bottom:0.5rem;">Description (optional):</label>
                <textarea name="description" id="description" rows="3"
                    style="width:100%; padding:0.5rem; margin-bottom:1.5rem; border-radius:6px; border:none; background-color:#1f1d1d; color:#e0e0e0;"></textarea>

                <label for="prompt" style="display:block; margin-bottom:0.5rem;">Custom Prompt:</label>
                <textarea name="prompt" id="prompt" rows="6" required placeholder="Buy when: ...&#10;Sell when: ..."
                    style="width:100%; padding:0.5rem; margin-bottom:1.5rem; border-radius:6px; border:none; background-color:#1f1d1d; color:#e0e0e0;"></textarea>

                <label style="display:flex; align-items:center; gap:10px; margin-bottom:1.5rem;">
                    <input style="width: auto;" type="checkbox" name="acknowledge" required>
                    I have read and understood the instruction tab.
                </label>

                <button type="submit" onclick="showNoti('<?php echo $message; ?>')"
                    style="padding:0.6rem 1.2rem; background-color:#00cccc; color:#000; border:none; border-radius:6px; font-weight:bold;">
                    Submit for Validation
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(id) {
    document.getElementById('create').style.display = 'none';
    document.getElementById('instruction').style.display = 'none';
    document.getElementById(id).style.display = 'block';
}

function showNoti($message) {
    alert($message);
    }
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    <?php if (!empty($message)): ?>
        showTab('create');
    <?php endif; ?>
});
</script>

<?php include 'common/footer.php'; ?>
