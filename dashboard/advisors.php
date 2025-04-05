<?php
require('../server.php');

// รับข้อมูลจาก POST
$advisors = isset($_POST['advisors']) ? json_decode($_POST['advisors'], true) : [];
$academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : null;
$partner_pending = isset($_POST['partner_pending']) && $_POST['partner_pending'] == 1;
$admin_pending = isset($_POST['admin_pending']) && $_POST['admin_pending'] == 1;
$advisor_pending = isset($_POST['advisor_pending']) && $_POST['advisor_pending'] == 1;

// สร้าง SQL Query
$sql = "SELECT 
            ar.advisor_request_id, 
            ar.student_id,
            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai, 
            ar.thesis_topic_eng, 
            ar.academic_year,
            ar.time_stamp 
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id";

// กำหนดเงื่อนไขตามประเภทหน้า
if ($partner_pending) {
    $sql .= " WHERE ar.partner_accepted = 0"; // Partner Pending
} elseif ($admin_pending) {
    $sql .= " WHERE ar.is_admin_approved = 0 AND ar.partner_accepted != 2 AND ar.is_advisor_approved != 2"; // Admin Pending
} elseif ($advisor_pending) {
    $sql .= " WHERE ar.is_advisor_approved = 0 AND ar.partner_accepted != 2 AND ar.is_admin_approved != 2"; // Advisor Pending
} else {
    $sql .= " WHERE (ar.partner_accepted = 1 AND ar.is_admin_approved = 1 AND ar.is_advisor_approved = 1)"; // Approved Requests
}

// เพิ่มเงื่อนไขปีการศึกษา
if ($academic_year != null) {
    $sql .= " AND ar.academic_year = '" . $conn->real_escape_string($academic_year) . "'";
}

// เพิ่มเงื่อนไขกรองชื่ออาจารย์
if (!empty($advisors)) {
    $advisor_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $advisors)) . "'";
    $sql .= " AND CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) IN ($advisor_list)";
}

$sql .= " ORDER BY ar.academic_year DESC";
$result = $conn->query($sql);

// สร้าง HTML สำหรับ tbody
$output = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = date('d/m/Y', strtotime($row['time_stamp']));
        $student_ids = json_decode($row['student_id'], true);
        $student_names = [];

        if (!empty($student_ids)) {
            $ids = "'" . implode("','", $student_ids) . "'";
            $name_query = "SELECT CONCAT(student_first_name, ' ', student_last_name) AS full_name 
                           FROM student 
                           WHERE student_id IN ($ids)";
            $name_result = $conn->query($name_query);
            while ($name_row = $name_result->fetch_assoc()) {
                $student_names[] = $name_row['full_name'];
            }
        }

        $student_full_name = !empty($student_names) ? implode(',<br>', $student_names) : 'ไม่พบชื่อนิสิต';
        $advisor_full_name = $row['advisor_full_name'] ?? 'ไม่พบชื่ออาจารย์';

        $output .= "<tr>
                        <td>{$row['advisor_request_id']}</td>
                        <td>{$row['student_id']}</td>
                        <td>{$student_full_name}</td>
                        <td>{$row['advisor_id']}</td>
                        <td>{$advisor_full_name}</td>
                        <td>{$row['thesis_topic_thai']}</td>
                        <td>{$row['thesis_topic_eng']}</td>
                        <td>{$row['academic_year']}</td>
                        <td>{$date}</td>
                    </tr>";
    }
} else {
    $message = $partner_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก Partner' : 
               ($admin_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก admin' : 
               ($advisor_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก Advisor' : 'ไม่มีคำขอที่ถูกอนุมัติ'));
    $output .= "<tr><td colspan='9' class='no-data'>$message</td></tr>";
}

echo $output;
?>