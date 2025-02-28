<?php
session_start();
require('../server.php');

if (isset($_POST['type']) && isset($_POST['offset']) && isset($_POST['receiver_id'])) {
    $type = $_POST['type'];
    $offset = (int)$_POST['offset'];
    $receiver_id = $_POST['receiver_id'];
    $id = $_SESSION['account_id'];
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
    $get_total = isset($_POST['get_total']) && $_POST['get_total'] === 'true';

    // ดึง timestamp การอนุมัติ
    $sql = "SELECT time_stamp FROM advisor_request 
            WHERE (
                (JSON_CONTAINS(student_id, '\"$id\"') AND advisor_id = '$receiver_id')
                OR 
                (advisor_id = '$id' AND JSON_CONTAINS(student_id, '\"$receiver_id\"'))
            ) 
            AND is_advisor_approved = 1 
            AND is_admin_approved = 1 
            ORDER BY time_stamp ASC LIMIT 1";
    $result = $conn->query($sql);
    $approval_timestamp = $result->num_rows > 0 ? $result->fetch_assoc()['time_stamp'] : null;

    $where_clause = "WHERE ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                    OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";

    if ($type === 'before' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp <= '$approval_timestamp'";
    } elseif ($type === 'after' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp > '$approval_timestamp'";
    }

    // ดึงข้อความ
    $sql = "
        SELECT message_title, MAX(time_stamp) AS latest_time
        FROM messages
        $where_clause
        GROUP BY message_title
        ORDER BY latest_time DESC
        LIMIT $offset, $limit
    ";
    $messages_result = $conn->query($sql);

    $messages = [];
    if ($messages_result) {
        while ($row = $messages_result->fetch_assoc()) {
            $messages[] = [
                'title' => $row['message_title'],
                'timestamp' => $row['latest_time'],
                'unread' => $conn->query("SELECT DISTINCT is_read FROM messages 
                                        WHERE receiver_id = '$id' 
                                        AND sender_id = '$receiver_id' 
                                        AND is_read = 0 
                                        AND message_title = '" . $conn->real_escape_string($row['message_title']) . "'")->num_rows > 0
            ];
        }
    }

    // สร้าง HTML สำหรับข้อความ
    $messages_html = '';
    if (empty($messages) && $offset === 0) {
        $messages_html = "<p>No messages found.</p>";
    } else {
        foreach ($messages as $message) {
            $messages_html .= "
            <div class='message' data-title='" . htmlspecialchars($message['title']) . "'>
                <div>
                    <div class='sender'>" . htmlspecialchars($message['title']) . "</div>
                    <div class='message-date'>" . $message['timestamp'] . "</div>
                </div>
                <div class='message-actions'>
                    <form action='../chat/index.php#chat-input' method='post' class='form-chat'>
                        <input type='hidden' name='title' value='" . htmlspecialchars($message['title']) . "'>
                        <button name='chat' class='menu-button' value='$receiver_id'><i class='bx bxs-message-dots'></i></button>";
            if ($message['unread']) {
                $messages_html .= "<span class='unread-indicator'><i class='bx bxs-circle'></i></span>";
            }
            $messages_html .= "
                    </form>
                    <div class='menu-container' data-title='" . htmlspecialchars($message['title']) . "'>
                        <button type='button' class='menu-button'><i class='bx bx-dots-vertical-rounded'></i></button>
                        <div class='dropdown-menu'>
                            <button type='button' class='delete-button' data-title='" . htmlspecialchars($message['title']) . "'>Delete</button>
                        </div>
                    </div>
                </div>
            </div>";
        }
    }

    // ดึงจำนวนทั้งหมด (ถ้าถูกขอ)
    $total = null;
    if ($get_total) {
        $sql_total = "
            SELECT COUNT(DISTINCT message_title) as total
            FROM messages
            $where_clause
        ";
        $result_total = $conn->query($sql_total);
        $total = $result_total->fetch_assoc()['total'];

        // ส่ง JSON สำหรับการลบ
        header('Content-Type: application/json');
        echo json_encode([
            'messages' => $messages_html,
            'total' => $total
        ]);
    } else {
        // ส่ง HTML ธรรมดาสำหรับ View More
        echo $messages_html;
    }
}
