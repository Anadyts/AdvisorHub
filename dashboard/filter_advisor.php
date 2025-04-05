<?php
require('../server.php');

// รับข้อมูลจาก POST
$advisors = isset($_POST['advisors']) ? json_decode($_POST['advisors'], true) : [];
$academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : null;
$is_even = isset($_POST['is_even']) ? json_decode($_POST['is_even'], true) : [];
$departments = isset($_POST['departments']) ? json_decode($_POST['departments'], true) : [];
$partner_pending = isset($_POST['partner_pending']) && $_POST['partner_pending'] == 1;
$admin_pending = isset($_POST['admin_pending']) && $_POST['admin_pending'] == 1;
$advisor_pending = isset($_POST['advisor_pending']) && $_POST['advisor_pending'] == 1;
$request_type = isset($_POST['request_type']) ? $_POST['request_type'] : null;

// สร้าง SQL Query
$sql = "SELECT 
            ar.advisor_request_id, 
            ar.student_id,
            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai, 
            ar.thesis_topic_eng, 
            ar.academic_year,
            ar.time_stamp,
            ar.partner_accepted,
            ar.is_admin_approved,
            ar.is_advisor_approved
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id";

// กำหนดเงื่อนไขตามประเภทหน้า
if ($partner_pending) {
    $sql .= " WHERE ar.partner_accepted = 0"; // Partner Pending
} elseif ($admin_pending) {
    $sql .= " WHERE ar.is_admin_approved = 0 AND ar.partner_accepted != 2 AND ar.is_advisor_approved != 2"; // Admin Pending
} elseif ($advisor_pending) {
    $sql .= " WHERE ar.is_advisor_approved = 0 AND ar.partner_accepted != 2 AND ar.is_admin_approved != 2"; // Advisor Pending
} elseif ($request_type === 'rejected') {
    $sql .= " WHERE (ar.partner_accepted = 2 OR ar.is_admin_approved = 2 OR ar.is_advisor_approved = 2)"; // Rejected Requests
} else {
    $sql .= " WHERE (ar.partner_accepted = 1 AND ar.is_admin_approved = 1 AND ar.is_advisor_approved = 1)"; // Approved Requests (default)
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

// เพิ่มเงื่อนไขกรอง is_even (เดี่ยว/คู่)
if (!empty($is_even)) {
    $is_even_list = implode(',', array_map('intval', $is_even));
    $sql .= " AND ar.is_even IN ($is_even_list)";
}

// เพิ่มเงื่อนไขกรองสาขา (student_department)
if (!empty($departments)) {
    $dept_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $departments)) . "'";
    $sql .= " AND EXISTS (
                SELECT 1 
                FROM student s 
                WHERE JSON_CONTAINS(ar.student_id, JSON_QUOTE(s.student_id)) 
                AND s.student_department IN ($dept_list)
            )";
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

        // เพิ่มคอลัมน์ "ถูกปฏิเสธโดย" เฉพาะหน้า Rejected Requests
        if ($request_type === 'rejected') {
            $rejected_by = [];
            if ($row['partner_accepted'] == 2) {
                $rejected_by[] = 'คู่ของนิสิต';
            }
            if ($row['is_admin_approved'] == 2) {
                $rejected_by[] = 'แอดมิน';
            }
            if ($row['is_advisor_approved'] == 2) {
                $rejected_by[] = 'อาจารย์';
            }
            $rejected_by_text = !empty($rejected_by) ? implode(', ', $rejected_by) : 'ไม่ทราบ';

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
                            <td>{$rejected_by_text}</td>
                        </tr>";
        } else {
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
    }
} else {
    $message = $partner_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก Partner' : 
               ($admin_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก admin' : 
               ($advisor_pending ? 'ไม่มีคำขอที่รอดำเนินการจาก Advisor' : 
               ($request_type === 'rejected' ? 'ไม่มีคำขอที่ถูกปฏิเสธ' : 'ไม่มีคำขอที่ถูกอนุมัติ')));
    $colspan = $request_type === 'rejected' ? 10 : 9;
    $output .= "<tr><td colspan='$colspan' class='no-data'>$message</td></tr>";
}

echo $output;
?>