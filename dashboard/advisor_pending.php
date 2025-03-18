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

$sql = "SELECT 
            advisor_request_id, 
            student_id, 
            thesis_topic_thai, 
            thesis_topic_eng, 
            time_stamp 
        FROM advisor_request 
        WHERE is_advisor_approved = 0 AND partner_accepted != 2 AND is_admin_approved != 2";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor pending</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/advisor_pending.css">
</head>
<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor','statistics']) ?>
    <h2 class="header">รายละเอียดคำขอจาก Advisor ที่รอดำเนินการ</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>รหัสคำขอ</th>
                    <th>รหัสนิสิต</th>
                    <th>หัวข้อวิทยานิพนธ์ (ไทย)</th>
                    <th>หัวข้อวิทยานิพนธ์ (อังกฤษ)</th>
                    <th>วันที่ร้องขอ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // แปลง timestamp เป็นรูปแบบวันที่ภาษาไทย
                        $date = $row['time_stamp'];
                        echo "<tr>
                                <td>{$row['advisor_request_id']}</td>
                                <td>{$row['student_id']}</td>
                                <td>{$row['thesis_topic_thai']}</td>
                                <td>{$row['thesis_topic_eng']}</td>
                                <td>{$date}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='no-data'>ไม่มีคำขอที่รอดำเนินการจาก Advisor</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>