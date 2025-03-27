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
            ar.advisor_request_id, 
            ar.student_id,  -- ดึง JSON array ของ student_id
            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai, 
            ar.thesis_topic_eng, 
            ar.academic_year,
            ar.time_stamp 
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE (ar.partner_accepted = 1 AND ar.is_admin_approved = 1 AND ar.is_advisor_approved = 1)";

if ($selected_year != null) {
    $sql .= " AND ar.academic_year = $selected_year";
}

$sql .= " ORDER BY ar.academic_year DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Requests</title>
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
                    <th style="width: 150px;">ชื่อนิสิต</th>
                    <th>รหัสอาจารย์</th>
                    <th style="width: 150px;">ชื่ออาจารย์</th>
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
                        $date = date('d/m/Y', strtotime($row['time_stamp']));

                        // แปลง JSON string เป็น array ใน PHP
                        $student_ids = json_decode($row['student_id'], true); // ได้ array เช่น ["65310000", "65310001"]
                        $student_names = [];

                        // ดึงชื่อนิสิตจากตาราง student
                        if (!empty($student_ids)) {
                            $ids = "'" . implode("','", $student_ids) . "'"; // แปลงเป็น "'65310000','65310001'"
                            $name_query = "SELECT CONCAT(student_first_name, ' ', student_last_name) AS full_name 
                                           FROM student 
                                           WHERE student_id IN ($ids)";
                            $name_result = $conn->query($name_query);

                            while ($name_row = $name_result->fetch_assoc()) {
                                $student_names[] = $name_row['full_name'];
                            }
                        }

                        // รวมชื่อนิสิต
                        $student_full_name = !empty($student_names) ? implode(',<br>', $student_names) : 'ไม่พบชื่อนิสิต';

                        // จัดการกรณี advisor_full_name เป็น NULL
                        $advisor_full_name = $row['advisor_full_name'] ?? 'ไม่พบชื่ออาจารย์';

                        echo "<tr>
                                <td>{$row['advisor_request_id']}</td>
                                <td>{$row['student_id']}</td>
                                <td>{$student_full_name}</td>
                                <td>{$row['advisor_id']}</td>
                                <td>{$advisor_full_name}</td>
                                <td>{$row['thesis_topic_thai']}</td>
                                <td>{$row['thesis_topic_eng']}</td>
                                <td>{$row['academic_year']}</td>
                                <td>{$date}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='no-data'>ไม่มีคำขอที่ถูกอนุมัติ</td></tr>"; // ปรับเป็น 9 คอลัมน์
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>