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

// ดึงค่าปีการศึกษาจาก GET
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

// ดึงปีการศึกษาที่มีอยู่ทั้งหมดจากฐานข้อมูล
$yearQuery = "SELECT DISTINCT academic_year FROM advisor_request ORDER BY academic_year DESC";
$yearResult = $conn->query($yearQuery);

// คิวรีข้อมูล โดยจะกรองปีการศึกษาถ้ามีค่า GET
$sql = "SELECT 
            advisor_request_id, 
            student_id, 
            thesis_topic_thai, 
            thesis_topic_eng, 
            academic_year,
            time_stamp 
        FROM advisor_request 
        WHERE partner_accepted = 0";

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
    <title>Partner pending</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/partner_pending.css">
</head>
<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor','statistics']) ?>
    
    <h2 class="header">รายละเอียดคำขอจาก Partner ที่รอดำเนินการ</h2>

    <!-- ฟอร์มเลือกปีการศึกษา -->
    <div class="filter-container">
        <form method="GET" class="filter-form">
            <label for="academic_year">ปีการศึกษา:</label>
            <select name="academic_year" id="academic_year" onchange="this.form.submit()">
                 <option value="">ทั้งหมด</option>
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
                        $date = date('d/m/Y', strtotime($row['time_stamp']));
                        echo "<tr>
                                <td>{$row['advisor_request_id']}</td>
                                <td>{$row['student_id']}</td>
                                <td>{$row['thesis_topic_thai']}</td>
                                <td>{$row['thesis_topic_eng']}</td>
                                <td>{$date}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='no-data'>ไม่มีคำขอที่รอดำเนินการจาก Partner</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>