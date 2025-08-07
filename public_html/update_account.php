<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/auth.php';
include 'common/db.php';

$user_id = $_SESSION['user_id'];

$first_name = trim($_POST['firstname']);
$last_name = trim($_POST['lastname']);
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if ($password && $password !== $confirm_password) {
    $_SESSION['account_update_message'] = "Passwords do not match.";
    header("Location: account.php?section=settings");
    exit();
}

if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = oci_parse($conn, "
        UPDATE Users 
        SET first_name = :fname, last_name = :lname, password_hash = :pwhash
        WHERE user_id = :user_name
    ");
    oci_bind_by_name($stmt, ':pwhash', $hashed);
} else {
    $stmt = oci_parse($conn, "
        UPDATE Users 
        SET first_name = :fname, last_name = :lname
        WHERE user_id = :user_name
    ");
}

oci_bind_by_name($stmt, ':fname', $first_name);
oci_bind_by_name($stmt, ':lname', $last_name);
oci_bind_by_name($stmt, ':user_name', $user_id);

if (oci_execute($stmt)) {
    oci_commit($conn);
    $_SESSION['account_update_message'] = "Account updated successfully.";
} else {
    $_SESSION['account_update_message'] = "Error updating account.";
}

header("Location: account.php?section=settings");
exit();
