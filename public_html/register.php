<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(strtolower($_POST['username']));
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $password  = trim($_POST['password']);

    if (!empty($username) && !empty($firstname) && !empty($lastname) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = oci_parse($conn, "
            INSERT INTO Users (user_id, first_name, last_name, password_hash)
            VALUES (:username, :firstname, :lastname, :password_hash)");

        oci_bind_by_name($stmt, ':username', $username);
        oci_bind_by_name($stmt, ':firstname', $firstname);
        oci_bind_by_name($stmt, ':lastname', $lastname);
        oci_bind_by_name($stmt, ':password_hash', $hashed_password);

        $result = oci_execute($stmt);

        if ($result) {
            oci_commit($conn);
            $success = "Registration successful! <a href='login.php'>Click here to login</a>.";
        } else {
            $e = oci_error($stmt);
            if (strpos($e['message'], 'ORA-00001') !== false) {
                $error = "Username already exists.";
            } else {
                $error = "Error: " . htmlentities($e['message']);
            }
        }

        oci_free_statement($stmt);
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<?php include('common/header.php'); ?>

<h2>Register</h2>
<div class="form-container">
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

    <form method="POST" action="register.php">
        <label for="username">User ID</label>
        <input type="text" name="username" id="username" required>

        <label for="firstname">First Name</label>
        <input type="text" name="firstname" id="firstname" required>

        <label for="lastname">Last Name</label>
        <input type="text" name="lastname" id="lastname" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<?php include('common/footer.php'); ?>
