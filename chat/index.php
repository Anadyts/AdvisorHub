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

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

if (isset($_POST['chat'])) {
    $_SESSION['receiver_id'] = $_POST['chat'];
    $_SESSION['title'] = $_POST['title'];
    header('location: /AdvisorHub/chat');
    exit();
}

if (empty($_SESSION['title'])) {
    header('location: /AdvisorHub/advisor');
    exit();
}

//ไม่ให้ admin เข้าถึง
if(isset($_SESSION['username']) && $_SESSION['role'] == 'admin'){
    header('location: /AdvisorHub/advisor');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.png">
</head>

<body>

    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

    <?php
    if (isset($_SESSION['receiver_id'])) {
        $receiver_id = $_SESSION['receiver_id'];
        $title = $_SESSION['title'];
        $account_id = $_SESSION['account_id'];

        // เมื่ออ่านแล้วเอาเครื่องหมายยังไม่อ่านออก
        $sql = "UPDATE messages SET is_read = 1 WHERE receiver_id = '$account_id' AND sender_id = '$receiver_id' AND is_read = 0 AND message_title = '$title'";
        $result = $conn->query($sql);

        $sql = "SELECT * FROM advisor WHERE advisor_id = '$receiver_id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        //เช็คว่า receiver เป็นใคร advisor หรือ student
        if (isset($row['advisor_first_name'])) {
            $first_name = $row['advisor_first_name'];
            $last_name = $row['advisor_last_name'];
        } else {
            $sql = "SELECT * FROM student WHERE student_id = '$receiver_id'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $first_name = $row['student_first_name'];
            $last_name = $row['student_last_name'];
        }

        // ตรวจสอบว่ามีการส่งข้อความหรือไม่
        if (isset($_POST['send'])) {
            $message = trim($_POST['message']); // ลบช่องว่างหน้า-หลัง
            $account_id = $_SESSION['account_id'];
            $receiver_id = $_SESSION['receiver_id'];
            $title = $_SESSION['title'];

            // ตัวแปรสำหรับไฟล์
            $fileName = null;
            $fileData = null;
            $fileType = null;

            // ตรวจสอบและจัดการไฟล์ที่อัปโหลด
            $hasFile = isset($_FILES['upload-file']) && $_FILES['upload-file']['error'] == UPLOAD_ERR_OK;
            if ($hasFile) {
                $fileName = $conn->real_escape_string($_FILES['upload-file']['name']);
                $fileType = $conn->real_escape_string($_FILES['upload-file']['type']);
                $fileTmpPath = $_FILES['upload-file']['tmp_name'];

                // อ่านข้อมูลไฟล์เป็น binary
                $fileData = file_get_contents($fileTmpPath);
                if ($fileData !== false) {
                    $fileData = $conn->real_escape_string($fileData);
                } else {
                    $fileData = null;
                }
            }

            // กำหนด $messageContent เป็น null ถ้าไม่มีข้อความ
            $messageContent = !empty($message) ? $conn->real_escape_string($message) : null;

            // เงื่อนไข: ต้องมีข้อความหรือไฟล์อย่างน้อยหนึ่งอย่าง
            if (!empty($messageContent) || $hasFile) {
                $sql = "INSERT INTO messages (sender_id, receiver_id, message_title, message, message_file_name, message_file_data, message_file_type) 
                            VALUES ('$account_id', '$receiver_id', '$title', " .
                    ($messageContent !== null ? "'$messageContent'" : "NULL") . ", " .
                    ($fileName ? "'$fileName'" : "NULL") . ", " .
                    ($fileData ? "'$fileData'" : "NULL") . ", " .
                    ($fileType ? "'$fileType'" : "NULL") . ")";
                $result = $conn->query($sql);

                if (!$result) {
                    echo "Error: " . $conn->error; // แสดงข้อผิดพลาดถ้ามี
                }
            }
        }

        echo "
                <div class='chat-container'>
                    <div class='chat-header' id='chat-header'>
                        <h2>$first_name $last_name</h2>
                    </div>
                    <div class='chat-box'>
                        <div class='message-container'>
            ";
        //แสดง messages
        $sql = "SELECT * FROM messages WHERE receiver_id = '$receiver_id' AND sender_id = '$account_id' AND message_title = '$title' UNION
                    SELECT * FROM messages WHERE receiver_id = '$account_id' AND sender_id = '$receiver_id' AND message_title = '$title'
                    ORDER BY time_stamp ASC";
        $result = $conn->query($sql);

        //แสดงข้อความ
        while ($row = $result->fetch_assoc()) {
            $message_id = $row['message_id'];
            $message = $row['message'];
            $time = $row['time_stamp'];
            $fileName = $row['message_file_name'];
            $fileData = $row['message_file_data'];
            $fileType = $row['message_file_type'];

            //ถ้า sender_id คือคนที่กำลัง login แปลว่าข้อความที่ดึงมาเป็นของคนที่ login อยู่
            if ($account_id == $row['sender_id']) {
                if (isset($message) && empty($fileName)) {
                    echo
                    "
                                <div class='message message-sent'>
                                    <div class='message-content'>" .  nl2br(string: htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                                    <div class='message-time'>$time</div>
                                </div>
                            ";
                } elseif (empty($message) && isset($fileName)) {
                    echo
                    "
                                <div class='message message-sent'>
                                    <div class='message-content'>
                                        <form action='download.php' method='POST'>
                                        <button name='file-clicked-message-id' value='" . htmlspecialchars($message_id, ENT_QUOTES, 'UTF-8') . "'>
                                            <i class='bx bx-file-blank'></i>
                                            <span>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</span>
                                        </button>
                                    </form>
                                    </div>
                                    <div class='message-time'>" . $time . "</div>
                                </div>
                            ";
                } elseif (isset($message) && isset($fileName)) {
                    echo
                    "
                                <div class='message message-sent'>
                                    <div class='message-content'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                                    <div class='message-content'>
                                        <form action='download.php' method='POST'>
                                        <button name='file-clicked-message-id' value='" . htmlspecialchars($message_id, ENT_QUOTES, 'UTF-8') . "'>
                                            <i class='bx bx-file-blank'></i>
                                            <span>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</span>
                                        </button>
                                    </form>
                                    </div>
                                    <div class='message-time'>" . $time . "</div>
                                </div>
                            ";
                }
            } else { //ถ้า sender_id ไม่ใช่คนที่กำลัง login แปลว่าข้อความที่ดึงมาเป็นของอีกฝั่ง
                if (isset($message) && empty($fileName)) {
                    echo
                    "
                        <div class='message message-received'>
                            <div class='message-content'>" .  nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                            <div class='message-time'>$time</div>
                        </div>
                    ";
                } elseif (empty($message) && isset($fileName)) {
                    echo
                    "
                            <div class='message message-received'>
                                <div class='message-content'>
                                    <form action='download.php' method='POST'>
                                        <button name='file-clicked-message-id' value='" . htmlspecialchars($message_id, ENT_QUOTES, 'UTF-8') . "'>
                                            <i class='bx bx-file-blank'></i>
                                            <span>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</span>
                                        </button>
                                    </form>
                                </div>
                                <div class='message-time'>" . $time . "</div>
                            </div>
                            ";
                } elseif (isset($message) && isset($fileName)) {
                    echo
                    "
                            <div class='message message-received'>
                                <div class='message-content'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                                <div class='message-content'>
                                    <form action='download.php' method='POST'>
                                        <button name='file-clicked-message-id' value='" . htmlspecialchars($message_id, ENT_QUOTES, 'UTF-8') . "'>
                                            <i class='bx bx-file-blank'></i>
                                            <span>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</span>
                                        </button>
                                    </form>
                                </div>
                                <div class='message-time'>" . $time . "</div>
                            </div>
                        ";
                }
            }
        }

        echo "
                        </div>
                    </div>
                    <form action='' method='post' class='form-send' enctype='multipart/form-data'>
                        <div class='chat-input' id='chat-input'>
                            <input type='text' class='input-message' name='message' placeholder='Type a message...' />
                            <div class='custom-file-upload'>
                                <input type='file' name='upload-file' id='file-input'>
                                <label for='file-input'><i class='bx bx-plus'></i></label>
                            </div>
                            <button class='send-button' name='send'><i class='bx bx-send bx-rotate-270'></i></button>
                        </div>
                        <div class='wrap-file-upload hidden'>
                            <div class='file-upload-detail'>
                                <span class='file-name-display'></span> 
                            </div>
                        </div>
                    </form>
            ";
    }

    ?>

    <button class="scroll-to-bottom"><i class='bx bx-down-arrow-alt'></i></button>

    </div>
    <script src="script.js"></script>
</body>

</html>