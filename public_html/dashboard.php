<?php

session_start();

// Include database connection file
include('common/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}