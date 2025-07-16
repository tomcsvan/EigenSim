<?php
// Start the session
session_start();

// Include database connection file
include 'common/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $firtname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $password = trim($_POST['password']);

    // Validate input
    if (!empty($username) && !empty($password) && !empty($email)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO Users (user_id, first_name, last_name, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sss", $username, $firtname, $lastname, $hashed_password);

        // Execute the statement
        if ($stmt->execute()) {
            echo "Registration successful!";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Please fill in all fields.";
    }
}

// Close the database connection
$conn->close();
include('common/header.php');
echo '<h2>Register</h2>';
echo '<form method="POST" action="">';
echo '<label for="username">Username:</label>';
echo '<input type="text" name="username" required><br>';

echo '<label for="password">Password:</label>';
echo '<input type="password" name="password" required><br>';

echo '<label for="email">Email:</label>';
echo '<input type="email" name="email" required><br>';

echo '<input type="submit" value="Register">';
echo '</form>';

include('common/footer.php');