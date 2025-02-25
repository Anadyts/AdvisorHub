<?php
session_start();
include('../components/navbar.php');
include('../server.php'); // เชื่อมต่อฐานข้อมูล

// จัดการการออกจากระบบ
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

//ไม่ให้ admin เข้าถึง
if(isset($_SESSION['username']) && $_SESSION['role'] == 'admin'){
    header('location: /AdvisorHub/advisor');
}

// เปลี่ยนหน้าไปโปรไฟล์
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

// ตรวจสอบว่าล็อกอินหรือยัง
if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

// จัดการการยอมรับคำร้อง
if (isset($_POST['accept'])) {
    $advisor_req_id = $_POST['accept'];
    $id = $_SESSION['account_id'];
    // ตรวจสอบว่า advisor เป็นเจ้าของคำร้อง
    $sql_check = "SELECT advisor_id FROM advisor_request WHERE advisor_request_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $advisor_req_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql = "UPDATE advisor_request SET partner_accepted = 1 WHERE advisor_request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $advisor_req_id);
        $stmt->execute();
    }
    header('location: /AdvisorHub/advisor_approved/request.php');
    exit();
}

// จัดการการปฏิเสธคำร้อง
if (isset($_POST['reject'])) {
    $advisor_req_id = $_POST['reject'];
    $id = $_SESSION['account_id'];
    // ตรวจสอบว่า advisor เป็นเจ้าของคำร้อง
    $sql_check = "SELECT advisor_id FROM advisor_request WHERE advisor_request_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $advisor_req_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql = "UPDATE advisor_request SET partner_accepted = 2 WHERE advisor_request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $advisor_req_id);
        $stmt->execute();
    }
    header('location: /AdvisorHub/advisor_approved/request.php');
    exit();
}

// กำหนดค่า id ของผู้ใช้ที่ล็อกอิน
$id = $_SESSION['account_id'];
?>

<html lang="th">

<head>
    <!-- กำหนด meta และ link สำหรับ CSS/JS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request</title>
    <link rel="stylesheet" href="style_request.css">
    <link rel="icon" href="../Logo.png">
</head>

<body>
    <!-- แสดง Navbar -->
    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

    <div class="container">
        <?php
        // ตรวจสอบบทบาทเป็น advisor
        if ($_SESSION['role'] == 'advisor') {
            // ดึงข้อมูลคำร้องที่อาจารย์ถูก request มา
            $sql = "SELECT advisor_request_id, student_id, thesis_topic_thai, is_advisor_approved, is_admin_approved, time_stamp 
                    FROM advisor_request 
                    WHERE advisor_id = ? AND is_advisor_approved = 0 AND is_admin_approved != 2 AND partner_accepted = 1
                    ORDER BY time_stamp DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id); // ใช้ "s" เพราะ $id อาจเป็น string
            $stmt->execute();
            $result = $stmt->get_result();

            // ตรวจสอบว่ามีคำร้องของ advisor นี้หรือไม่
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    // แปลง student_id จาก JSON เป็น array
                    $student_ids = json_decode($row['student_id'], true);
                    if (!is_array($student_ids)) {
                        $student_ids = [$row['student_id']];
                    }
        ?>
                    <div class="card">
                        <!-- แสดงหัวข้อวิทยานิพนธ์ -->
                        <h5 class="card-title">หัวข้อวิทยานิพนธ์: <?php echo htmlspecialchars($row["thesis_topic_thai"]); ?></h5>
                        <ul class="list-group">
                            <?php foreach ($student_ids as $student_id):
                                // ดึงข้อมูลนักเรียนจาก student_id
                                $sql_student = "SELECT * FROM student WHERE student_id = '$student_id'";
                                $result_student = $conn->query($sql_student);
                                $row_name = $result_student->fetch_assoc();
                            ?>
                                <!-- แสดงข้อมูลนักเรียน -->
                                <li class="list-group-item">
                                    <strong>รหัสนิสิต:</strong> <?php echo htmlspecialchars($student_id); ?>
                                    <strong>ชื่อ:</strong> <?php echo htmlspecialchars($row_name['student_first_name']) . " " . htmlspecialchars($row_name['student_last_name']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <!-- แสดงปุ่มรายละเอียดและ timestamp -->
                        <div class="wrap-foot d-flex align-items-center mt-3">
                            <form action="details.php" method="POST">
                                <input type="hidden" name='advisor_request_id' value=<?php echo $row['advisor_request_id'] ?>>
                                <button type="submit" class="btn-orange">รายละเอียด</button>
                            </form>
                            <span class="timestamp"> <?php echo $row["time_stamp"]; ?></span>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <!-- แสดงข้อความเมื่อไม่มีคำร้อง -->
                <div class="no-request-message">
                    <p>คุณยังไม่มีคำร้อง</p>
                </div>
            <?php endif; ?>

            <?php
            $stmt->close();
            $conn->close();

            // ตรวจสอบบทบาทเป็น student
        } elseif ($_SESSION['role'] == 'student') {
            // ดึงข้อมูลคำร้องที่เกี่ยวข้องกับ student_id
            $sql = "SELECT *
                    FROM advisor_request 
                    WHERE JSON_CONTAINS(student_id, ?) 
                    ORDER BY time_stamp DESC";

            $stmt = $conn->prepare($sql);
            $json_id = json_encode([$id]);
            $stmt->bind_param("s", $json_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // ตรวจสอบว่ามีคำร้องหรือไม่
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $requester_id = $row['requester_id'];
                    $partner_accepted = $row['partner_accepted'];
                    $advisor_req_id = $row['advisor_request_id'];
                    $is_even = $row['is_even'];
                    // กำหนดสถานะคำร้องของ partner
                    if ($partner_accepted == 0) {
                        $partner_status = '<span class="status waiting">Waiting</span>';
                    } elseif ($partner_accepted == 1) {
                        $partner_status = '<span class="status approved">Accepted</span>';
                    } elseif ($partner_accepted == 2) {
                        $partner_status = '<span class="status rejected">Rejected</span>';
                    }

                    // กำหนดสถานะคำร้องของ advisor
                    if ($row['is_advisor_approved'] == 0) {
                        $advisor_status = '<span class="status waiting">Waiting</span>';
                    } elseif ($row['is_advisor_approved'] == 1) {
                        $advisor_status = '<span class="status approved">Approved</span>';
                    } elseif ($row['is_advisor_approved'] == 2) {
                        $advisor_status = '<span class="status rejected">Rejected</span>';
                    }

                    // กำหนดสถานะคำร้องของ admin
                    if ($row['is_admin_approved'] == 0) {
                        $admin_status = '<span class="status waiting">Waiting</span>';
                    } elseif ($row['is_admin_approved'] == 1) {
                        $admin_status = '<span class="status approved">Approved</span>';
                    } elseif ($row['is_admin_approved'] == 2) {
                        $admin_status = '<span class="status rejected">Rejected</span>';
                    }
            ?>
                    <div class="request-card">
                        <!-- แสดงหัวข้อวิทยานิพนธ์ -->
                        <h3 class="request-title">หัวข้อวิทยานิพนธ์: <?php echo htmlspecialchars($row["thesis_topic_thai"]); ?></h3>
                        <div class="request-info">
                            <?php
                            // แสดงสถานะ partner ถ้าเป็นกลุ่มคู่
                            if ($is_even == 1) { ?>
                                <p><strong>Partner Accept Status:</strong> <?php echo $partner_status; ?></p>
                            <?php } ?>
                            <!-- แสดงสถานะของ advisor และ admin -->
                            <p><strong>Advisor Approval Status:</strong> <?php echo $advisor_status; ?></p>
                            <p><strong>Admin Approval Status:</strong> <?php echo $admin_status; ?></p>
                        </div>
                        <!-- แสดง timestamp -->
                        <div class="request-footer">
                            <span class="timestamp"><?php echo $row["time_stamp"]; ?></span>
                        </div>
                        <!-- แสดงปุ่มยอมรับ/ปฏิเสธสำหรับ partner -->
                        <?php if ($requester_id != $id && $partner_accepted == 0) {
                            echo
                            "
                                    <form action='' method='post' class='form-choose'>
                                        <div class='wrapChoose'>
                                            <button name='accept' class='accept' value='$advisor_req_id'>Accept Partner Request</button>
                                        </div>
                                        <div class='wrapChoose'>
                                            <button name='reject' class='reject' value='$advisor_req_id'>Reject Partner Request</button>
                                        </div>
                                    </form>
                                    ";
                        } elseif ($requester_id != $id && $partner_accepted == 1) {
                            echo "<div class='status-partner'><h3 class='accept-text'>You Accepted Partner</h3></div>";
                        } elseif ($requester_id != $id && $partner_accepted == 2) {
                            echo "<div class='status-partner'><h3 class='reject-text'>You Rejected Partner</h3></div>";
                        }
                        ?>
                    </div>
                <?php endwhile;
            else: ?>
                <!-- แสดงข้อความเมื่อไม่มีคำร้อง -->
                <div class="no-request-message">
                    <p>คุณยังไม่มีคำร้อง</p>
                </div>
            <?php endif; ?>

        <?php
            $stmt->close();
            $conn->close();
        }
        ?>
    </div>
</body>

</html>