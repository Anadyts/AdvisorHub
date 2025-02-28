<?php
session_start();
require('../server.php'); // เชื่อมต่อกับฐานข้อมูล
ini_set('display_errors', 1);
error_reporting(E_ALL);

// รับ message_id จาก POST
$message_id = $_POST['message_id'] ?? null;
if (!$message_id) {
    echo json_encode(['error' => 'Message ID not provided']);
    exit;
}

try {
    // ตรวจสอบไฟล์ในตาราง messages
    $sql = "SELECT m.*, a.role, 
                   CASE 
                       WHEN a.role = 'student' THEN (SELECT student_first_name FROM student WHERE student_id = m.sender_id)
                       WHEN a.role = 'advisor' THEN (SELECT advisor_first_name FROM advisor WHERE advisor_id = m.sender_id)
                       ELSE m.sender_id
                   END AS uploader_name
            FROM messages m
            LEFT JOIN account a ON m.sender_id = a.account_id
            WHERE m.message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if (!$file) {
        throw new Exception('File not found');
    }

    // ตรวจสอบข้อมูลผู้ใช้ปัจจุบัน
    $current_user_role = $_SESSION['role'];
    $current_username = $_SESSION['username'];

    // เริ่มจากกำหนดว่ายังไม่มีสิทธิ์
    $can_delete = false;

    // เพิ่มการแสดงข้อมูลเพื่อดีบัก
    $debug_info = [
        'role' => $current_user_role,
        'username' => $current_username,
        'uploader_name' => $file['uploader_name'],
        'sender_id' => $file['sender_id']
    ];

    // กรณีเป็นผู้ส่งไฟล์ (เจ้าของไฟล์)
    if ($current_user_role === 'student' || $current_user_role === 'advisor') {
        if ($file['sender_id'] === $_SESSION['account_id']) {
            $can_delete = true;
        }
    } 
    // กรณีเป็น admin
    elseif ($current_user_role === 'admin') {
        $can_delete = true;
    }

    // เพิ่มข้อมูลว่ามีสิทธิ์ลบหรือไม่
    $debug_info['can_delete'] = $can_delete;

    // ดำเนินการลบไฟล์
    if ($can_delete) {
        $sql = "DELETE FROM messages WHERE message_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $message_id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to delete file: ' . $conn->error);
        }

        // ลบไฟล์จากระบบ (ตรวจสอบว่ามีไฟล์ในระบบจริงๆ)
        if ($file['message_file_data'] && file_exists($file['message_file_data'])) {
            unlink($file['message_file_data']);
        }

        echo json_encode(['success' => true]);
    } else {
        // ส่งข้อมูลดีบักกลับไปด้วย
        throw new Exception("Oh, sorry, you don't have permission to delete this file because you are not the owner of the file.");
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
