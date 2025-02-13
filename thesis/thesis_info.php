<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /ThesisAdvisorHub/login');
}

if (empty($_SESSION['username'])) {
    header('location: /ThesisAdvisorHub/login');
}

if (isset($_POST['profile'])) {
    header('location: /ThesisAdvisorHub/profile');
}

if (isset($_SESSION['advisor_id'])) {
    $advisor_id = $_SESSION['advisor_id'];

    // ดึงข้อมูลจากตาราง thesis
    $sql = "SELECT * FROM thesis WHERE advisor_id = '$advisor_id'";
    $result = $conn->query($sql);
    $row_thesis = $result->fetch_assoc();

    if ($row_thesis) {
        $id = $row_thesis['id'];
        $title = $row_thesis['title'];
        $authors = nl2br(implode("\n", explode(',', $row_thesis['authors'])));
        $keywords = str_replace(['[', ']'], '', $row_thesis['keywords']);
        $issue_date = $row_thesis['issue_date'];
        $publisher = $row_thesis['publisher'];
        $abstract = $row_thesis['abstract'];
        $uri = $row_thesis['uri'];
        $thesis_file = $row_thesis['thesis_file'];
    } else {
        echo "ไม่พบข้อมูลวิทยานิพนธ์";
    }

    // ลบเครื่องหมาย " และเพิ่มช่องว่างหลังจุลภาค
    $keyword_array = explode(',', str_replace('"', '', $keywords));

    // ใช้ Regular Expression แยกภาษาไทย และภาษาอังกฤษ
    $title_parts = preg_split('/(?=[A-Z])/', $title, 2); // แยกเมื่อเจออักษรภาษาอังกฤษตัวใหญ่

    $thai_title = trim($title_parts[0]); // ส่วนของภาษาไทย
    $english_title = isset($title_parts[1]) ? trim($title_parts[1]) : ""; // ส่วนของภาษาอังกฤษ (ถ้ามี)

    // ดึงข้อมูลของที่ปรึกษา (ชื่อ-นามสกุล)
    $sql_advisor = "SELECT first_name, last_name FROM advisor WHERE id = '$advisor_id'";
    $result_advisor = $conn->query($sql_advisor);
    $row_advisor = $result_advisor->fetch_assoc();

    if ($row_advisor) {
        $advisor_name = $row_advisor['first_name'] . " " . $row_advisor['last_name'];
    } else {
        $advisor_name = "ไม่พบชื่อที่ปรึกษา";
    }
} else {
    echo "ไม่พบข้อมูล";
}

$uri = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Information</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../Logo.png">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

    <div class="container">
        <h1>Thesis Information</h1>
        <div class="info">Title: <span><?php echo $thai_title; ?></span><br>
            <span><?php echo $english_title; ?></span>
        </div>
        <div class="info">Authors: <span><?php echo $authors; ?></span></div>
        <div class="info">Advisor Name: <span><?php echo $advisor_name; ?></span></div>
        <div class="info">Keywords:<br>
            <span><?php echo implode('<br>', $keyword_array); ?></span>
        </div>
        <div class="info">Issue Date: <span><?php echo $issue_date; ?></span></div>
        <div class="info">Publisher: <span><?php echo $publisher; ?></span></div>
        <div class="info">Abstract: <span><?php echo $abstract; ?></span></div>
        <div class="info">URI: <span><a href="<?php echo $uri; ?>"><?php echo $uri; ?></a></span></div>

        <div class="downloadf button mt-4">
            <a href="download.php?id=<?php echo $row_thesis['id']; ?>" class="btn">
                Download Thesis File
            </a>
        </div>
    </div>

</body>

</html>