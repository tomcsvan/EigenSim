<?php

session_start();
// Include database connection file
include('common/db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the username and password from the form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input
    if (!empty($username) && !empty($password)) {
        // Prepare SQL statement
        $stmt = oci_parse($conn, "SELECT user_id, password_hash FROM Users WHERE user_id = :username");
        oci_bind_by_name($stmt, ':username', $username);

        // Execute the statement
        oci_execute($stmt);
        
        // Fetch the result
        $row = oci_fetch_assoc($stmt);
        
        if ($row && password_verify($password, $row['PASSWORD_HASH'])) {
            // Set session variables
            $_SESSION['user_id'] = $row['USER_ID'];
            echo "Login successful!";
            // Redirect to a protected page or dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Invalid username or password.";
        }

        // Close the statement
        oci_free_statement($stmt);
    } else {
        echo "Please fill in all fields.";
    }
}