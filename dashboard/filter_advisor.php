<?php
require('../server.php');

$selectedAdvisors = isset($_POST['advisors']) ? json_decode($_POST['advisors'], true) : [];
$selectedYear = isset($_POST['academic_year']) && !empty($_POST['academic_year']) ? $_POST['academic_year'] : null;
$requestType = isset($_POST['request_type']) ? $_POST['request_type'] : 'accepted'; // กำหนดค่าเริ่มต้นเป็น 'accepted'

// กำหนดเงื่อนไขการค้นหาตามประเภทคำขอ
if ($requestType == 'rejected') {
    $condition = "(ar.partner_accepted = 2 OR ar.is_admin_approved = 2 OR ar.is_advisor_approved = 2)";
} else { // accepted เป็นค่าเริ่มต้น
    $condition = "(ar.partner_accepted = 1 AND ar.is_admin_approved = 1 AND ar.is_advisor_approved = 1)";
}

$sql = "SELECT 
            ar.advisor_request_id, 
            ar.student_id,
            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai, 
            ar.thesis_topic_eng, 
            ar.academic_year,
            ar.time_stamp";

// เพิ่มฟิลด์สถานะการอนุมัติเฉพาะเมื่อเป็นคำขอที่ถูกปฏิเสธ
if ($requestType == 'rejected') {
    $sql .= ",
            ar.partner_accepted,
            ar.is_admin_approved,
            ar.is_advisor_approved";
}

$sql .= " FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE " . $condition;

if (!empty($selectedAdvisors)) {
    $advisorConditions = [];
    foreach ($selectedAdvisors as $advisor) {
        $advisorConditions[] = "CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) = '$advisor'";
    }
    $sql .= " AND (" . implode(" OR ", $advisorConditions) . ")";
}

if ($selectedYear != null) {
    $sql .= " AND ar.academic_year = $selectedYear";
}

$sql .= " ORDER BY ar.academic_year DESC";
$result = $conn->query($sql);

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

        // แสดงผลข้อมูลตามประเภทคำขอ
        if ($requestType == 'rejected') {
            // ตรวจสอบว่าใครเป็นคนปฏิเสธ
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

            echo "<tr>
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
        } else { // คำขอที่อนุมัติ
            echo "<tr>
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
    if ($requestType == 'rejected') {
        echo "<tr><td colspan='10' class='no-data'>ไม่มีคำขอที่ถูกปฏิเสธ</td></tr>";
    } else {
        echo "<tr><td colspan='9' class='no-data'>ไม่มีคำขอที่ถูกอนุมัติ</td></tr>";
    }
}
