<?php
session_start();
include('../components/navbar.php');
require('../server.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        session_destroy();
        header('location: /AdvisorHub/login');
        exit();
    }

    if (isset($_POST['profile'])) {
        header('location: /AdvisorHub/profile');
        exit();
    }

    if (isset($_POST['chat'])) {
        $_SESSION['receiver_id'] = $_POST['chat'];
        header('location: /AdvisorHub/topic_chat/topic_chat.php');
        exit();
    }

    if (isset($_POST['advisor_request'])) {
        $_SESSION['advisor_id'] = $_POST['advisor_request'];
        header('location: /AdvisorHub/request/');
        exit();
    }

    if (isset($_POST['thesis'])) {
        $_SESSION['advisor_id'] = $_POST['thesis'];
        header('location: /AdvisorHub/thesis/thesis_history.php');
        exit();
    }

    if (isset($_POST['info'])) {
        $_SESSION['advisor_info_id'] = $_POST['info'];
        header('location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    //ไม่ให้ admin เข้าถึง
    if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        header('location: /AdvisorHub/advisor');
    }
}

// ดึงข้อมูลที่ปรึกษาจาก session
$advisor_info = null;
if (isset($_SESSION['advisor_info_id'])) {
    $advisor_id = $_SESSION['advisor_info_id'];

    $sql = "SELECT * FROM advisor_profile WHERE advisor_id = '$advisor_id'";
    $result = $conn->query($sql);
    $advisor_info = $result->fetch_assoc();

    if ($advisor_info) {
        $advisor_info['expertise'] = json_decode($advisor_info['expertise']);
        $advisor_info['interests'] = nl2br($advisor_info['advisor_interests']);

        $sql = "SELECT * FROM advisor WHERE advisor_id = '$advisor_id'";
        $result_advisor = $conn->query($sql);
        $advisor_data = $result_advisor->fetch_assoc();

        if ($advisor_data) {
            $advisor_info = array_merge($advisor_info, $advisor_data);
        }

        // นับจำนวนนักศึกษาที่ให้คำปรึกษา
        $sql = "
            SELECT SUM(JSON_LENGTH(student_id)) AS student_count
            FROM advisor_request
            WHERE advisor_id = '$advisor_id'
            AND is_advisor_approved = 1
            AND is_admin_approved = 1
            AND time_stamp BETWEEN STR_TO_DATE(CONCAT(YEAR(CURDATE()) - 1, '-11-01'), '%Y-%m-%d')
            AND STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-10-31'), '%Y-%m-%d');
        ";

        $result = $conn->query($sql);
        $advisor_info['student_count'] = $result ? $result->fetch_assoc()['student_count'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor List</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.png">
</head>

<body>
    <?php
    if (isset($_SESSION['username'])) {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>

    <?php if ($advisor_info): ?>
        <div class="container">
            <div class="profile-info">
                <img src="<?= $advisor_info['img']; ?>" alt="Advisor Image">
                <h2><?= $advisor_info['advisor_first_name'] . " " . $advisor_info['advisor_last_name']; ?></h2>
            </div>

            <div class="contact-info">
                <h3>Contact</h3>
                <p><strong>Email:</strong> <?= $advisor_info['advisor_email']; ?></p>
                <p><strong>Telephone Number:</strong> <?= $advisor_info['advisor_tel']; ?></p>
            </div>

            <div class="research-info">
                <h3>Expertise</h3>
                <?php foreach ($advisor_info['expertise'] as $item): ?>
                    <p><?= $item; ?></p>
                <?php endforeach; ?>

                <h3>Interests</h3>
                <p><?= $advisor_info['advisor_interests']; ?></p>

                <h3>Number of advising students: <?= $advisor_info['student_count'] ?? 0; ?></h3>
            </div>

            <form action="" method="post" class="nav-form">
                <button name="advisor_request" value="<?= $advisor_info['advisor_id']; ?>">
                    <img src="./gif/write.gif" alt="" width="70" height="70">
                </button>
                <button name="thesis" value="<?= $advisor_info['advisor_id']; ?>">
                    <img src="./gif/history.gif" alt="" width="70" height="70">
                </button>
                <button name="chat" value="<?= $advisor_info['advisor_id']; ?>">
                    <img src="./gif/message.gif" alt="" width="70" height="70">
                </button>
            </form>
        </div>
    <?php else: ?>
        <p>No advisor information to display.</p>
    <?php endif; ?>

</body>

</html>