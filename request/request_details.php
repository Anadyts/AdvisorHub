<?php
include("../server.php");
include('../components/navbar.php');
session_start();
// if ($_SESSION['previous_page'] != "advisor_requset.php") {
//     header("location:http://localhost/AdvisorHub/request/");
// }

// จัดการ Logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
    exit();
}

// จัดการ Profile
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

// ดึงข้อมูลจาก db ที่ไม่ได้ถูกปฏิเสธ
$sql = "SELECT * FROM advisor_request WHERE JSON_CONTAINS(student_id, '\"{$_SESSION["account_id"]}\"') AND is_advisor_approved != 2";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_array($result);
    $student_ids = json_decode($row['student_id'], true);
} else {
    header('location: /AdvisorHub/advisor_approved/request.php');
}

//ไม่ให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียด</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../Logo.png">
</head>

<body>
    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="container text-center w-65 shadow-lg rounded-2 p-5">
            <h2 class="mb-3">รายละเอียดการส่งคำร้อง</h2>

            <p class="badge
            <?php
            if (isset($_SESSION['notify_message'])) {
                if ($_SESSION['notify_message'] == 'ส่งคำร้องสำเร็จ') {
                    echo 'text-bg-primary';
                } else {
                    echo 'text-bg-warning';
                }
            }
            ?> 
            text-wrap">
                สถานะการส่งคำร้อง:
                <?php
                if (isset($_SESSION['notify_message'])) {
                    echo $_SESSION['notify_message'];
                }
                // เคลียร์ข้อความแจ้งเตือน
                // unset($_SESSION['notify_message']);
                ?>
            </p>

            <div class="container rounded-2 p-3" style="background-color: #f1f1f1;">
                <p>รหัสนิสิต
                    <?php
                    if (count($student_ids) > 1) {
                        echo $student_ids[0] . ' และ ' . $student_ids[1];
                    } else {
                        echo $student_ids[0];
                    }
                    ?>
                </p>

                <p>อาจารย์ที่ปรึกษา:
                    <?php
                    $sql_advisor = "SELECT advisor_first_name, advisor_last_name FROM advisor WHERE advisor_id = '{$row["advisor_id"]}'";
                    $result_advisor = mysqli_query($conn, $sql_advisor);
                    if (mysqli_num_rows($result_advisor) > 0) {
                        $row_advisor = mysqli_fetch_array($result_advisor);
                        echo $row_advisor["advisor_first_name"] . " " . $row_advisor["advisor_last_name"];
                    } else {
                        echo "ไม่พบข้อมูล";
                    }
                    ?>
                </p>

                <p> ชื่อเรื่องภาษาไทย:
                    <?php
                    echo $row['thesis_topic_thai'];
                    ?>
                </p>

                <p> ชื่อเรื่องภาษาอังกฤษ:
                    <?php
                    echo $row['thesis_topic_eng'];
                    ?>
                </p>

                <p>
                    รายละเอียดวิทยานิพนธ์โดยสังเขป:
                    <?php
                    echo $row['thesis_description'];
                    ?>
                </p>
            </div>

            <p class="text-danger mt-2">
                หากข้อมูลส่งคำร้องของคุณไม่ถูกต้อง โปรดติดต่อเจ้าหน้าที่
            </p>
            <a href="/AdvisorHub/advisor_approved/request.php">
                <button class="btn mt-3" style="color:white; background-color: #ff9300;">
                    กลับสู่หน้าหลัก
                </button>
            </a>
        </div>
    </div>
</body>

</html>