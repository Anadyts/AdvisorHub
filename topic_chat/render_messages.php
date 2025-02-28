<?php
// ฟังก์ชันสำหรับแสดงข้อความ
function renderMessages($messages, $receiver_id, $total = null, $offset = 0, $type = null, $search_term = '', $conn = null, $id = null)
{
    $messages_html = '';
    if (empty($messages) && $offset === 0) {
        $messages_html = "<p>No messages found.</p>";
    } else {
        foreach ($messages as $message) {
            // ดึงสถานะการร้องขอลบ
            $delete_request = 0;
            $delete_from_id = null;
            if ($conn && $id) {
                $title = $conn->real_escape_string($message['title']);
                $sql = "SELECT message_delete_request, message_delete_from_id 
                        FROM messages 
                        WHERE message_title = '$title' 
                        AND ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                             OR (sender_id = '$receiver_id' AND receiver_id = '$id')) 
                        LIMIT 1";
                $result = $conn->query($sql);
                if ($result && $row = $result->fetch_assoc()) {
                    $delete_request = $row['message_delete_request'] ?? 0;
                    $delete_from_id = $row['message_delete_from_id'] ?? null;
                }
            }

            $messages_html .= "
            <div class='message' data-title='" . htmlspecialchars($message['title']) . "'>
                <div>
                    <div class='sender'>" . htmlspecialchars($message['title']) . "</div>
                    <div class='message-date'>" . $message['timestamp'] . "</div>";

            // ตรวจสอบสถานะการลบ
            if ($delete_request == 0) {
                // ยังไม่มีการร้องขอ
            } elseif ($delete_from_id == $id) {
                // ผู้ใช้ร้องขอแล้ว รอการยืนยัน
                $messages_html .= "
                    <div class='delete-status'>
                        <span>Delete Status: <span class='status-text'>Waiting</span></span>
                    </div>";
            } else {
                // ถูกขอให้ยืนยันการลบ
                $messages_html .= "
                    <div class='message-options'>
                        <span class='confirm-text'>Do you want to confirm the deletion?</span>
                        <button type='button' class='approve-button' data-title='" . htmlspecialchars($message['title']) . "'><i class='fa-regular fa-circle-check'></i></button>
                        <button type='button' class='reject-button' data-title='" . htmlspecialchars($message['title']) . "'><i class='fa-regular fa-circle-xmark'></i></button>
                    </div>";
            }

            $messages_html .= "
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

    return $messages_html;
}
