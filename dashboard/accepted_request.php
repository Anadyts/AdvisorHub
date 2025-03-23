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

if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
    header('location: /AdvisorHub/home');
    exit();
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

// ดึงค่าปีการศึกษาจาก GET
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

// ดึงปีการศึกษาที่มีอยู่ทั้งหมดจากฐานข้อมูล
$yearQuery = "SELECT DISTINCT academic_year FROM advisor_request ORDER BY academic_year DESC";
$yearResult = $conn->query($yearQuery);

// คิวรีข้อมูลที่ถูกปฏิเสธ โดยจะกรองปีการศึกษาถ้ามีค่า GET
$sql = "SELECT 
            advisor_request_id, 
            student_id, 
            advisor_id,
            thesis_topic_thai, 
            thesis_topic_eng, 
            academic_year,
            time_stamp 
        FROM advisor_request 
        WHERE (partner_accepted = 1 AND is_admin_approved = 1 AND is_advisor_approved = 1)";

if ($selected_year != null) {
    $sql .= " AND academic_year = $selected_year";
}

$sql .= " ORDER BY academic_year DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected request</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/accepted_request.css">
</head>
<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor', 'statistics']) ?>

    <h2 class="header">รายละเอียดคำขอจากนิสิตที่อนุมัติ</h2>

    <!-- ฟอร์มเลือกปีการศึกษา -->
    <div class="filter-container">
        <form method="GET" class="filter-form">
            <label for="academic_year">ปีการศึกษา:</label>
            <select name="academic_year" id="academic_year" onchange="this.form.submit()">
                 <option value="">ทั้งหมด</option> <!-- ค่าเริ่มต้น: แสดงทั้งหมด -->
                 <?php
                 while ($row = $yearResult->fetch_assoc()) {
                     $year = $row['academic_year'];
                     $selected = ($year == $selected_year) ? 'selected' : '';
                     echo "<option value='$year' $selected>$year</option>";
                 }
                 ?>
            </select>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>รหัสคำขอ</th>
                    <th>รหัสนิสิต</th>
                    <th>รหัสอาจารย์</th>
                    <th>หัวข้อวิทยานิพนธ์ (ไทย)</th>
                    <th>หัวข้อวิทยานิพนธ์ (อังกฤษ)</th>
                    <th>ปีการศึกษา</th>
                    <th>วันที่ร้องขอ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $date = date('d/m/Y', strtotime($row['time_stamp'])); // แปลง timestamp
                        echo "<tr>
                                <td>{$row['advisor_request_id']}</td>
                                <td>{$row['student_id']}</td>
                                <td>{$row['advisor_id']}</td>
                                <td>{$row['thesis_topic_thai']}</td>
                                <td>{$row['thesis_topic_eng']}</td>
                                <td>{$row['academic_year']}</td>
                                <td>{$date}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>ไม่มีคำขอที่ถูกอนุมัติ</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>