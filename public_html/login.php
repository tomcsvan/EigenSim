<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('common/db.php');

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /~tomcsvan/dashboard.php");
    exit();
}

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = oci_parse($conn, "
            SELECT user_id, password_hash 
            FROM Users 
            WHERE LOWER(user_id) = LOWER(:username)");
        oci_bind_by_name($stmt, ':username', $username);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        if ($row && password_verify($password, $row['PASSWORD_HASH'])) {
            $_SESSION['user_id'] = $row['USER_ID'];
            echo 'login as' . $row['USER_ID'];
            header("Location: /~tomcsvan/dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password." . $password . " HERE " . $row['password_hash'];
        }

        oci_free_statement($stmt);
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<?php include 'common/header.php' ; ?>

<h2 style="text-align:center; font-size:1.8rem; color:#00cccc; margin-bottom:1rem;">Login</h2>
<div class="form-container">
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST" action="login.php">
        <label for="username">User ID</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
</div>

<?php include 'common/footer.php'; ?>
