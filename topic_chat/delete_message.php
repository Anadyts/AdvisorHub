<?php
session_start();
require('../server.php');

if (!isset($_POST['title']) || !isset($_POST['receiver_id']) || !isset($_POST['action'])) {
    echo 'error';
    exit();
}

$title = $conn->real_escape_string($_POST['title']);
$receiver_id = $conn->real_escape_string($_POST['receiver_id']);
$id = $_SESSION['account_id'];
$action = $_POST['action'];

if ($action === 'request') {
    // ส่งคำร้องขอลบ (สถานะ: Waiting)
    $sql = "UPDATE messages 
            SET message_delete_request = 1, message_delete_from_id = '$id', time_stamp = NOW()
            WHERE message_title = '$title' 
            AND ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                 OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";
    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error';
    }
} elseif ($action === 'approve') {
    // ยอมรับคำร้อง ลบข้อความจริงๆ
    $sql = "DELETE FROM messages 
            WHERE message_title = '$title' 
            AND ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                 OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";
    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error';
    }
} elseif ($action === 'reject') {
    // ปฏิเสธคำร้อง รีเซ็ตสถานะ
    $sql = "UPDATE messages 
            SET message_delete_request = 0, message_delete_from_id = NULL, time_stamp = NOW() 
            WHERE message_title = '$title' 
            AND ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                 OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";
    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
