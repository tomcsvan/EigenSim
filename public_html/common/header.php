<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'common/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>EigenSim - Algorithmic Trading Platform</title>
  <link rel="stylesheet" href="/~tomcsvan/assets/styles.css">
</head>
<body>
<header id="header">
  <div class="header-left">
    <img src="/~tomcsvan/assets/logo.png" alt="EigenSim Logo" id="icon">
    <h1>EigenSim</h1>
  </div>
  <nav class="header-right">
    <ul class="tabs">
      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="/~tomcsvan/dashboard.php">Dashboard</a></li>
        <li><a href="/~tomcsvan/backtest_select_ticker.php">Backtest</a></li>
        <li class="dropdown">
          <a href="#">Account</a>
          <ul class="dropdown-content">
            <li><a href="/~tomcsvan/account.php">Account Settings</a></li>
            <li><a href="/~tomcsvan/logout.php">Logout</a></li>
          </ul>
        </li>
      <?php else: ?>
        <li><a href="/~tomcsvan/index.php">Home</a></li>
        <li><a href="/~tomcsvan/login.php">Login</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
<div id="main">
