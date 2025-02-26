<?php
session_start();
require('../server.php');
include('../components/navbar.php');

// จัดการการออกจากระบบ (logout)
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// ไม่อนุญาตให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

// ตรวจสอบว่า session ว่างหรือไม่
if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

// จัดการการเปลี่ยนเส้นทางไปยังหน้าโปรไฟล์และหน้าแชท
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

if (isset($_POST['chat'])) {
    $_SESSION['receiver_id'] = $_POST['chat'];
    header('location: /AdvisorHub/topic_chat/topic_chat.php');
}

if (isset($_POST['profileInbox'])) {
    $id = $_POST['profileInbox'];
    $_SESSION['profileInbox'] = $id;
    $_SESSION['advisor_info_id'] = $id;
    $role = getUserRole($id);

    if ($role == 'advisor') {
        header('location: /AdvisorHub/info');
    } else {
        header('location: /AdvisorHub/student_profile');
    }
}

// ฟังก์ชัน helper สำหรับดึงข้อมูลบทบาทผู้ใช้
function getUserRole($id)
{
    global $conn;
    $sql = "SELECT role FROM account WHERE account_id = '$id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['role'];
}

// ฟังก์ชัน helper สำหรับดึงข้อมูลผู้ใช้ (อาจารย์ที่ปรึกษาหรือนักศึกษา) โดยมีคีย์ที่กำหนดมาตรฐาน
function getUserInfo($id)
{
    global $conn;
    // ตรวจสอบว่าเป็นอาจารย์ที่ปรึกษาหรือไม่
    $sql = "SELECT advisor_id AS id, advisor_first_name AS first_name, advisor_last_name AS last_name 
            FROM advisor WHERE advisor_id = '$id'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        return $row; // คืนข้อมูลอาจารย์ที่ปรึกษาโดยมีคีย์ที่กำหนดมาตรฐาน
    }

    // ตรวจสอบว่าเป็นนักศึกษาหรือไม่
    $sql = "SELECT student_id AS id, student_first_name AS first_name, student_last_name AS last_name 
            FROM student WHERE student_id = '$id'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        return $row; // คืนข้อมูลนักศึกษาโดยมีคีย์ที่กำหนดมาตรฐาน
    }

    return null; // คืนค่า null หากไม่พบผู้ใช้
}

// ฟังก์ชัน helper สำหรับตรวจสอบข้อความที่ยังไม่ได้อ่าน
function checkUnreadMessages($receiver_id, $sender_id)
{
    global $conn;
    $sql = "SELECT DISTINCT * FROM messages WHERE receiver_id = '$receiver_id' AND is_read = 0 AND sender_id = '$sender_id'";
    $result = $conn->query($sql);
    return $result->fetch_assoc(); // คืนค่าผลลัพธ์หากมีข้อความที่ยังไม่ได้อ่าน
}

// ฟังก์ชันสำหรับแสดงรายละเอียดผู้ใช้ (อาจารย์ที่ปรึกษาหรือนักศึกษา)
function displayUserDetails($userInfo)
{
    if (!$userInfo) return; // ข้ามหากไม่มีข้อมูลผู้ใช้

    echo "<div class='message'>
            <div class='sender'>{$userInfo['first_name']} {$userInfo['last_name']}</div>
            <form action='' method='post' class='form-chat'>
                <button name='profileInbox' class='profileInbox' value='{$userInfo['id']}'><i class='bx bxs-user-pin'></i></button>
                <button name='chat' class='chat-button' value='{$userInfo['id']}'><i class='bx bxs-message-dots'></i></button>";

    // ตรวจสอบข้อความที่ยังไม่ได้อ่าน
    $unreadMessages = checkUnreadMessages($_SESSION['account_id'], $userInfo['id']);
    if ($unreadMessages) {
        echo "<i class='bx bxs-circle'></i>";
    }

    echo "</form></div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.png">
</head>

<body>

    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>
    <div class="inbox-container">
        <div class="inbox-head">
            <h2>Inbox</h2>
        </div>
        <div class="inbox">
            <?php
            $id = $_SESSION['account_id'];

            // คำสั่ง SQL สำหรับดึงข้อมูล ID ผู้ใช้ที่ไม่ซ้ำกันพร้อมกับ timestamp ล่าสุด
            $sql = "SELECT 
                        CASE 
                            WHEN sender_id = '$id' THEN receiver_id 
                            ELSE sender_id 
                        END AS user_id,
                        MAX(time_stamp) AS latest_timestamp
                    FROM messages
                    WHERE sender_id = '$id' OR receiver_id = '$id'
                    GROUP BY user_id
                    ORDER BY latest_timestamp DESC";

            $result = $conn->query($sql);

            $user_data = [];
            while ($row = $result->fetch_assoc()) {
                $user_data[] = $row;
            }

            // วนลูปและแสดงผล
            foreach ($user_data as $user) {
                $userInfo = getUserInfo($user['user_id']);
                displayUserDetails($userInfo);
            }
            ?>
        </div>
    </div>

    <footer>
        <p>© 2024 Naresuan University.</p>
    </footer>
</body>

</html>