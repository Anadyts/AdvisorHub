<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin' || empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}
// ตรวจสอบว่า student_id และ advisor_id ถูกระบุมาหรือไม่
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id'])) {
    header("Location: admin_chat_management.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];

// คำสั่งคิวรี่เพื่อดึงหัวข้อข้อความที่แตกต่างกันระหว่างนักเรียนและที่ปรึกษา
$sql = "
    SELECT DISTINCT 
        m.message_title
    FROM 
        messages m
    WHERE 
        (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY 
        m.message_title DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $student_id, $advisor_id, $advisor_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Chat Titles</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../Logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
        }

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

        .title-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .title-item span {
            font-weight: bold;
        }

        .title-item button {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .title-item button:hover {
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
    if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } elseif (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>

    <div class="container">
        <h1>Chat Titles</h1>
        <a href="index.php" class="back-btn">Back to Chat Management</a>

        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
        ?>
                <div class="title-item">
                    <span><?php echo htmlspecialchars($row['message_title']); ?></span>
                    <button onclick="window.location.href='chat_details.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($row['message_title']); ?>'">View</button>
                </div>
        <?php
            }
        } else {
            echo "<p>No message titles found between this student and advisor.</p>";
        }
        ?>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>