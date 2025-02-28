<?php
session_start();
require('../server.php');
include('render_messages.php');

// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!isset($_POST['receiver_id']) || !isset($_POST['type'])) {
    exit();
}

$search_term = isset($_POST['search']) ? trim($_POST['search']) : '';
$receiver_id = $_POST['receiver_id'];
$type = $_POST['type'];
$id = $_SESSION['account_id'];
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['results_per_page']) ? (int)$_POST['results_per_page'] : 5;
$offset = ($page - 1) * $limit;

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

// สร้างเงื่อนไข WHERE
$where_clause = "WHERE ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";

if ($type === 'before' && $approval_timestamp !== null) {
    $where_clause .= " AND time_stamp <= '$approval_timestamp'";
    $where_clause .= " AND message_title NOT IN (
        SELECT DISTINCT message_title 
        FROM messages 
        WHERE ((sender_id = '$id' AND receiver_id = '$receiver_id') 
               OR (sender_id = '$receiver_id' AND receiver_id = '$id'))
               AND time_stamp > '$approval_timestamp'
    )";
} elseif ($type === 'after' && $approval_timestamp !== null) {
    $where_clause .= " AND time_stamp > '$approval_timestamp'";
}

if (!empty($search_term)) {
    $search_term_escaped = $conn->real_escape_string($search_term);
    $where_clause .= " AND message_title LIKE '%$search_term_escaped%'";
}

// ดึงข้อความตามหน้า
$sql = "
    SELECT message_title, MAX(time_stamp) AS latest_time,
           MAX(message_delete_request) AS delete_request, 
           MAX(message_delete_from_id) AS delete_from_id
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
                                    AND message_title = '" . $conn->real_escape_string($row['message_title']) . "'")->num_rows > 0,
            'delete_request' => $row['delete_request'],
            'delete_from_id' => $row['delete_from_id']
        ];
    }
}

// ดึงจำนวนข้อความทั้งหมด
$sql_total = "
    SELECT COUNT(DISTINCT message_title) as total
    FROM messages
    $where_clause
";
$result_total = $conn->query($sql_total);
$total = $result_total->fetch_assoc()['total'];

// ปรับการคำนวณผลลัพธ์
if ($total > 0) {
    $start_result = $offset + 1;
    $end_result = min($offset + $limit, $total);
} else {
    $start_result = 0;
    $end_result = 0;
}

// แสดงผลข้อความ
$messages_html = renderMessages($messages, $receiver_id, $total, $offset, $type, $search_term, $conn, $id);

// เพิ่มการแบ่งหน้า
$total_pages = ceil($total / $limit);
if ($total_pages > 1) {
    $messages_html .= '<div class="pagination">';
    // Prev button
    if ($page > 1) {
        $messages_html .= "<a href='#' class='pagination-arrow' data-page='" . ($page - 1) . "'>«</a>";
    } else {
        $messages_html .= "<a href='#' class='pagination-arrow disabled'>«</a>";
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = $i == $page ? 'active' : '';
        $messages_html .= "<a href='#' class='$active' data-page='$i'>$i</a>";
    }

    // Next button
    if ($page < $total_pages) {
        $messages_html .= "<a href='#' class='pagination-arrow' data-page='" . ($page + 1) . "'>»</a>";
    } else {
        $messages_html .= "<a href='#' class='pagination-arrow disabled'>»</a>";
    }
    $messages_html .= '</div>';
}

// เพิ่มข้อมูลผลลัพธ์ เฉพาะเมื่อมีข้อความ
if ($total > 0) {
    $messages_html .= "<div class='results-info'>";
    $messages_html .= "Results: $start_result - $end_result of $total messages";
    $messages_html .= "<select class='results-per-page' onchange='updateResultsPerPage(this.value, \"$type\")'>";
    $messages_html .= "<option value='5' " . ($limit == 5 ? 'selected' : '') . ">5</option>";
    $messages_html .= "<option value='10' " . ($limit == 10 ? 'selected' : '') . ">10</option>";
    $messages_html .= "<option value='20' " . ($limit == 20 ? 'selected' : '') . ">20</option>";
    $messages_html .= "<option value='$total' " . ($limit == $total ? 'selected' : '') . ">All</option>";
    $messages_html .= "</select>";
    $messages_html .= "</div>";
}

header('Content-Type: text/html; charset=utf-8');
echo $messages_html;
