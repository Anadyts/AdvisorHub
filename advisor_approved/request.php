<?php
    session_start();
    include('../components/navbar.php');
    if(isset($_POST['logout'])){
        session_destroy();
        header('location: /AdvisorHub/login');
    }

    if(isset($_POST['profile'])){
        header('location: /AdvisorHub/profile');
    }

    if(empty($_SESSION['username'])){
        header('location: /AdvisorHub/login');
    }

    if(isset($_SESSION['username']) && $_SESSION['role'] == 'student' || $_SESSION['role'] == 'admin'){
        header('location: /AdvisorHub/advisor');
    }

?>

<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติคำร้อง</title>
    <link rel="stylesheet" href="style_request.css">
    <link rel="icon" href="../Logo.png">

</head>
<body>
    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams'])?>

    <div class="container">
        <?php
        date_default_timezone_set("Asia/Bangkok");
        
        $thesis_list = [
            ["title" => "การพัฒนาโปรแกรมจัดการข้อมูล", "eng_title" => "Development of Data Management Software", "students" => [["name" => "aaaa aaa", "id" => "123456789", "major" => "IT"]]],
            ["title" => "การพัฒนา AI สำหรับคัดกรองเอกสาร", "eng_title" => "Development of AI for Document Screening", "students" => [["name" => "bbbb bbb", "id" => "987654321", "major" => "CS"]]],
            ["title" => "ระบบจัดเก็บข้อมูลออนไลน์อัจฉริยะ", "eng_title" => "Intelligent Online Data Storage System", "students" => [["name" => "cccc ccc", "id" => "112233445", "major" => "CS"]]],
            ["title" => "การวิเคราะห์ข้อมูลขนาดใหญ่", "eng_title" => "Big Data Analytics", "students" => [["name" => "dddd ddd", "id" => "556677889", "major" => "IT"]]]
        ];
        
        foreach ($thesis_list as $index => $thesis): 
            $timestamp = date("d-m-Y h:i:s"); ?>
            <div class="card">
                <h5 class="card-title">หัวข้อวิทยานิพนธ์: <?php echo $thesis["title"]; ?></h5>
                <h6 class="card-subtitle">(<?php echo $thesis["eng_title"]; ?>)</h6>
                <ul class="list-group">
                    <?php foreach ($thesis["students"] as $student): ?>
                        <li class="list-group-item">
                            <strong>ชื่อ:</strong> <?php echo $student["name"]; ?> &nbsp;
                            <strong>รหัสนิสิต:</strong> <?php echo $student["id"]; ?> &nbsp;
                            <strong>สาขาวิชา:</strong> <?php echo $student["major"]; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex align-items-center mt-3">
                    <a href="details.php?id=<?php echo $index + 1; ?>" class="btn-orange">รายละเอียด</a>
                    <span class="timestamp"> <?php echo $timestamp; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>