<?php
session_start();
require('../server.php');
include('../components/navbar.php');

if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin' || empty($_SESSION['username'])) {
    // ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่ และไม่ใช่แอดมิน หรือไม่มี username ในเซสชัน
    header('location: /AdvisorHub/login');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นครบถ้วนหรือไม่
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id']) || !isset($_GET['title'])) {
    header("Location: view_chat.php"); // ถ้าขาดพารามิเตอร์ เปลี่ยนเส้นทางไปยังหน้า view_chat.php
    exit();
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];
$message_title = $_GET['title'];

// คำสั่ง SQL เพื่อดึงข้อมูลข้อความตามหัวข้อที่ระบุ
$sql = "
    SELECT 
        m.message_id, 
        m.message_title, 
        m.message, 
        m.message_file_name, 
        m.message_file_type,
        m.time_stamp, 
        CASE 
            WHEN m.sender_id = s.student_id THEN CONCAT(s.student_first_name, ' ', s.student_last_name)
            WHEN m.sender_id = a.advisor_id THEN CONCAT(a.advisor_first_name, ' ', a.advisor_last_name)
        END AS sender_name 
    FROM 
        messages m
    LEFT JOIN 
        student s ON s.student_id = m.sender_id OR s.student_id = m.receiver_id 
    LEFT JOIN 
        advisor a ON a.advisor_id = m.sender_id OR a.advisor_id = m.receiver_id 
    WHERE 
        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.message_title = ? 
    ORDER BY 
        m.time_stamp DESC 
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiis", $student_id, $advisor_id, $advisor_id, $student_id, $message_title);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Details - <?php echo htmlspecialchars($message_title); ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../Logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
        }

        /* สไตล์พื้นฐานของหน้า */
        .container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .message {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .message span {
            font-weight: bold;
        }

        .message p {
            margin: 5px 0;
        }

        .download-btn {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .download-btn:hover {
            background: #0056b3;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #ccc;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background: #bbb;
        }
    </style>
</head>

<body>
    <?php
    // แสดงแถบนำทางตามบทบาทของผู้ใช้
    if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } elseif (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>

    <div class="container">
        <h1>Chat Details - <?php echo htmlspecialchars($message_title); ?></h1>
        <a href="view_chat.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>" class="back-btn">Back to Chat Title</a>

        <?php
        if (mysqli_num_rows($result) > 0) {
            // ถ้ามีข้อความในผลลัพธ์
            while ($row = mysqli_fetch_assoc($result)) {
                // วนลูปแสดงข้อความแต่ละข้อ
        ?>
                <div class="message">
                    <span><?php echo htmlspecialchars($row['sender_name']); ?> - <?php echo htmlspecialchars($row['time_stamp']); ?></span>
                    <p><strong>ข้อความ:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                    <?php if (!empty($row['message_file_name'])) { ?>
                        <p><strong>ไฟล์:</strong> <?php echo htmlspecialchars($row['message_file_name']); ?> (<?php echo htmlspecialchars($row['message_file_type']); ?>)
                        <form action="download_file.php" method="POST" style="display: inline;">
                            <input type="hidden" name="message_id" value="<?php echo $row['message_id']; ?>">
                            <button type="submit" class="download-btn">ดาวน์โหลด</button>
                        </form>
                        </p>
                    <?php } ?>
                </div>
        <?php
            }
        } else {
            echo "<p>ไม่พบข้อความสำหรับหัวข้อนี้</p>";
        }
        ?>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>