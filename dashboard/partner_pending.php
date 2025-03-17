<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
    exit();
}

if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

if(isset($_SESSION['username']) && $_SESSION['role'] != 'admin'){
    header('location: /AdvisorHub/home');
}
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner pending</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/partner_pending.css">
</head>
<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor','statistics']) ?>
</body>
</html>