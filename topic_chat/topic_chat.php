<?php
session_start();
require('../server.php');
include('../components/navbar.php');

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

if(empty($_SESSION['receiver_id']) || $_SESSION['receiver_id'] == $_SESSION['id']){
    header('location: /AdvisorHub/advisor');
}

if (isset($_POST['profileInbox'])) {
    $user_id = $_POST['profileInbox'];
    $_SESSION['profileInbox'] = $user_id;

    $sql = "SELECT role FROM advisor WHERE id = '$user_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if ($row['role'] == 'advisor') {
        header('location: /AdvisorHub/advisor_profile');
    } else {
        header('location: /AdvisorHub/student_profile');
    }
}

$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="topic_chat.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    $receiver_id = $_SESSION['receiver_id'];
    $sql = "SELECT first_name, last_name FROM advisor WHERE id = '$receiver_id' UNION SELECT first_name, last_name FROM student WHERE id = '$receiver_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo 
    "
    <div class='topic-container'>
        <div class='topic-head'>
            <h2>{$row['first_name']} {$row['last_name']}</h2>
    ";
    ?>

            <a href="topic_create.php" class="fa-solid fa-circle-plus"></a>
        </div>
        <form method="POST" class="topic-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Search topic" value="<?php echo $search_query; ?>" />
            <button type="submit"><i class='bx bx-search'></i></button>
        </form>

        <div class="topic-status">
            <button class="active">In progress</button>
            <button>Completed</button>
        </div>
        
        <?php
            $id = $_SESSION['id'];
            $receiver_id = $_SESSION['receiver_id'];

            // ถ้ามีการค้นหาก็จะใช้คำค้นหานั้นในการกรอง
            $search_condition = "";
            if (!empty($search_query)) {
                $search_condition = " AND title LIKE '%$search_query%' ";
            }

            $sql = "
                    SELECT title, MAX(time_stamp) AS latest_time
                    FROM messages
                    WHERE (sender_id = '$id' AND receiver_id = '$receiver_id') 
                    OR (sender_id = '$receiver_id' AND receiver_id = '$id')
                    $search_condition
                    GROUP BY title
                    ORDER BY 
                        CASE 
                            WHEN title LIKE '%$search_query%' THEN 1 
                            ELSE 2 
                        END,
                        latest_time DESC
                ";
            $result = $conn->query($sql);
            
            while($row = $result->fetch_assoc()){
                $title = $row['title'];
                $timestamp = $row['latest_time'];
                echo 
                "
                <div class='divider'></div>
                <div class='message'>
                    <div>
                        <div class='sender'>$title</div>
                        <div class='message-date'>$timestamp</div>
                    </div>
                <form action='../chat/index.php' method='post' class='form-chat'>
                    <input type='hidden' name='title' value='$title'>
                    <button name='chat' class='chat-button' value='$receiver_id'><i class='bx bxs-message-dots'></i></button>
                    ";

                    $query = "SELECT DISTINCT is_read FROM messages WHERE receiver_id = '$id' AND sender_id = '$receiver_id' AND is_read = 0 AND title = '$title'";
                    $result_is_read = $conn->query($query);

                    // ตรวจสอบว่ามีการส่งข้อความที่ยังไม่ได้อ่าน
                    if ($result_is_read && $result_is_read->num_rows > 0) {
                        echo "<i class='bx bxs-circle'></i>"; // วงกลมสีที่บ่งบอกว่ามีข้อความใหม่
                    }
                    
                    echo"
                </form>
                </div>
            ";
            }
        ?>
        
    </div>
</body>
</html>
