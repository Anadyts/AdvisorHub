<?php
session_start();
include('../components/navbar.php');
include('../server.php'); // เชื่อมต่อฐานข้อมูล

// จัดการการออกจากระบบ
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// ตรวจสอบว่าล็อกอินหรือยัง
if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if ($_SESSION['role'] != 'admin') {
    header('location: /AdvisorHub/advisor');
}

// ดึงข้อมูลคำร้องทั้งหมดที่รอการอนุมัติจากแอดมิน พร้อมข้อมูลอาจารย์
$sql = "SELECT ar.advisor_request_id, ar.student_id, ar.thesis_topic_thai, 
               ar.is_advisor_approved, ar.is_admin_approved, ar.time_stamp, 
               a.advisor_first_name, a.advisor_last_name 
        FROM advisor_request ar
        JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE ar.is_admin_approved = 0 
        ORDER BY ar.time_stamp DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management</title>
    <link rel="stylesheet" href="style_approve.css">
    <link rel="icon" href="../Logo.png">
</head>
<body>
    <?php 
    if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } elseif (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>
    <div class="container">
        <h1>การจัดการคำร้อง</h1>
<!-- ภายในลูป while สำหรับแสดงผลคำร้อง -->
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card">
        <h5 class="card-title">หัวข้อวิทยานิพนธ์: <?php echo htmlspecialchars($row["thesis_topic_thai"]); ?></h5>
        <ul class="list-group">
            <?php
            // แปลง student_id จาก JSON เป็น array
            $student_ids = json_decode($row['student_id'], true);
            if (!is_array($student_ids)) {
                $student_ids = [$row['student_id']];
            }
            foreach ($student_ids as $student_id):
                $sql_student = "SELECT * FROM student WHERE student_id = '$student_id'";
                $result_student = $conn->query($sql_student);
                $row_name = $result_student->fetch_assoc();
            ?>
                <li class="list-group-item">
                    <strong>รหัสนิสิต:</strong> <?php echo htmlspecialchars($student_id); ?><br>
                    <strong>ชื่อ:</strong> <?php echo htmlspecialchars($row_name['student_first_name']) . " " . htmlspecialchars($row_name['student_last_name']); ?><br>
                    
                    <?php

                    ?>
                </li>
                <div class="wrap-advisor">
                
                    <?php endforeach; 
                        echo htmlspecialchars($row['advisor_first_name']) . " " . htmlspecialchars($row['advisor_last_name']);
                        if ($row['is_advisor_approved'] == 0) {
                            echo " (รอการอนุมัติ)";
                        } elseif ($row['is_advisor_approved'] == 1) {
                            echo " (อนุมัติแล้ว)";
                        } elseif ($row['is_advisor_approved'] == 2) {
                            echo " (ปฏิเสธแล้ว)";
                        }
                    ?>
                </div>


        </ul> <!-- ปิด <ul> หลังจาก loop foreach -->

        <div class="wrap-foot d-flex align-items-center mt-3">
            <form action="details.php" method="POST">
                <input type="hidden" name="advisor_request_id" value="<?php echo $row['advisor_request_id']; ?>">
                <button type="submit" class="btn-orange details">รายละเอียด</button>
            </form>
            <span class="timestamp"><?php echo $row["time_stamp"]; ?></span>
        </div>
    </div>
<?php endwhile; ?>
<?php else: ?>
    <div class="no-request-message">
        <p>ไม่มีคำร้องรอการอนุมัติ</p>
    </div>
<?php endif; ?>

        <?php
        $stmt->close();
        $conn->close();
        ?>
    </div>
</body>

</html>
