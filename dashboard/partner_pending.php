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

if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
    header('location: /AdvisorHub/home');
    exit();
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

// ดึงค่าจาก GET
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$search_student_id = isset($_GET['search_student_id']) ? trim($_GET['search_student_id']) : '';
$search_student_name = isset($_GET['search_student_name']) ? trim($_GET['search_student_name']) : '';
$search_thesis_topic = isset($_GET['search_thesis_topic']) ? trim($_GET['search_thesis_topic']) : '';

$selected_advisors = isset($_GET['advisors']) && is_array($_GET['advisors']) ? $_GET['advisors'] : [];
$selected_is_even = isset($_GET['is_even']) && is_array($_GET['is_even']) ? $_GET['is_even'] : [];
$selected_departments = isset($_GET['departments']) && is_array($_GET['departments']) ? $_GET['departments'] : [];

// ดึงปีการศึกษาที่มีอยู่ทั้งหมดจากฐานข้อมูล

$yearQuery = "SELECT DISTINCT academic_year FROM advisor_request ORDER BY academic_year DESC";
$yearResult = $conn->query($yearQuery);

// ดึงรายชื่ออาจารย์ทั้งหมดสำหรับตัวเลือกใน select box

$advisorQuery = "SELECT DISTINCT CONCAT(advisor_first_name, ' ', advisor_last_name) AS advisor_full_name
                 FROM advisor ORDER BY advisor_first_name, advisor_last_name";
$advisorResult = $conn->query($advisorQuery);

// คิวรีข้อมูลที่รอการอนุมัติจาก partner
$sql = "SELECT
            ar.advisor_request_id,
            ar.student_id,
            ar.requester_id,  

            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai,
            ar.thesis_topic_eng,
            ar.academic_year,
            ar.time_stamp,
            ar.is_even
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE ar.partner_accepted = 0";

// เพิ่มเงื่อนไขการกรอง
if (!empty($selected_year)) {
    $sql .= " AND ar.academic_year = '" . $conn->real_escape_string($selected_year) . "'";
}
if (!empty($search_student_id)) {
    $sql .= " AND JSON_CONTAINS(ar.student_id, '\"$search_student_id\"')";
}
if (!empty($search_student_name)) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM student s
        WHERE JSON_CONTAINS(ar.student_id, CONCAT('\"', s.student_id, '\"'))
        AND CONCAT(s.student_first_name, ' ', s.student_last_name) LIKE '%$search_student_name%'
    )";
}
if (!empty($search_thesis_topic)) {
    $sql .= " AND (ar.thesis_topic_thai LIKE '%$search_thesis_topic%' OR ar.thesis_topic_eng LIKE '%$search_thesis_topic%')";
}
if (!empty($selected_advisors)) {
    $advisor_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $selected_advisors)) . "'";
    $sql .= " AND CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) IN ($advisor_list)";
}
if (!empty($selected_is_even)) {
    $is_even_list = implode(',', array_map('intval', $selected_is_even));
    $sql .= " AND ar.is_even IN ($is_even_list)";
}
if (!empty($selected_departments)) {

    $dept_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $selected_departments)) . "'";
    $sql .= " AND EXISTS (
        SELECT 1 FROM student s
        WHERE JSON_CONTAINS(ar.student_id, CONCAT('\"', s.student_id, '\"'))
        AND s.student_department IN ($dept_list)
    )";
}

$sql .= " ORDER BY ar.academic_year DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Pending</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/partner_pending.css">

    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
</head>


<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor', 'statistics']) ?>

    <h2 class="header">รายละเอียดคำขอจาก Partner ที่รอดำเนินการ</h2>


    <!-- การ์ดกรองทั้งหมด -->
    <div class="filter-card">
        <h3>ค้นหาและกรองข้อมูลคำร้องขอที่รอ Partner อนุมัติ</h3>

        <form method="GET" action="" id="filterForm">
            <div class="form-row">
                <label>ปีการศึกษา:
                    <select name="academic_year" id="academic_year">
                        <option value="">ทั้งหมด</option>
                        <?php while ($row = $yearResult->fetch_assoc()): ?>
                            <option value="<?= $row['academic_year'] ?>" <?= $row['academic_year'] == $selected_year ? 'selected' : '' ?>>
                                <?= $row['academic_year'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <input type="text" name="search_student_id" placeholder="ค้นหาด้วยรหัสนิสิต" value="<?= htmlspecialchars($search_student_id) ?>">

                <input type="text" name="search_student_name" placeholder="ค้นหาด้วยชื่อ-นามสกุลนิสิต" value="<?= htmlspecialchars($search_student_name) ?>">

                <input type="text" name="search_thesis_topic" placeholder="ค้นหาด้วยหัวข้อวิทยานิพนธ์" value="<?= htmlspecialchars($search_thesis_topic) ?>">

                <button type="submit">ค้นหา</button>
            </div>

            <h6>ตัวกรองเพิ่มเติม</h6>
            <div class="filter-row">
                <div class="filter-item">
                    <label>กรองชื่ออาจารย์</label>
                    <select name="advisors[]" id="select-advisors" multiple data-placeholder="กรองชื่ออาจารย์">

                        <optgroup label="Advisor">
                            <?php
                            $advisorResult->data_seek(0);
                            while ($row = $advisorResult->fetch_assoc()) {
                                $advisor_name = htmlspecialchars($row['advisor_full_name'], ENT_QUOTES, 'UTF-8');

                                $selected = in_array($advisor_name, $selected_advisors) ? 'selected' : '';
                                echo "<option value='$advisor_name' $selected>$advisor_name</option>";
                            }
                            ?>
                        </optgroup>
                    </select>
                </div>

                <div class="filter-item">
                    <label>กรองประเภทการทำ</label>
                    <select name="is_even[]" id="select-is-even" multiple data-placeholder="เลือกประเภท (เดี่ยว/คู่)">
                        <option value="0" <?= in_array('0', $selected_is_even) ? 'selected' : '' ?>>เดี่ยว</option>
                        <option value="1" <?= in_array('1', $selected_is_even) ? 'selected' : '' ?>>คู่</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>กรองสาขา</label>
                    <select name="departments[]" id="select-department" multiple data-placeholder="เลือกสาขา">
                        <option value="Information Technology" <?= in_array('Information Technology', $selected_departments) ? 'selected' : '' ?>>Information Technology</option>
                        <option value="Computer Science" <?= in_array('Computer Science', $selected_departments) ? 'selected' : '' ?>>Computer Science</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>รหัสคำขอ</th>
                    <th>รหัสนิสิต</th>
                    <th style="width: 150px;">ชื่อนิสิต</th>
                    <th>รหัสอาจารย์</th>
                    <th style="width: 150px;">ชื่ออาจารย์</th>
                    <th>หัวข้อวิทยานิพนธ์ (ไทย)</th>
                    <th>หัวข้อวิทยานิพนธ์ (อังกฤษ)</th>
                    <th>ปีการศึกษา</th>
                    <th>วันที่ร้องขอ</th>
                    <th>ผู้ส่งคำขอ</th> 
                </tr>
            </thead>
            <tbody id="requestTableBody">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $date = date('d/m/Y', strtotime($row['time_stamp']));
                        $student_ids = json_decode($row['student_id'], true);
                        $student_names = [];

                        // ดึงชื่อนิสิตจากรหัสนิสิต
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

                        // แปลง student_ids จาก array เป็น string ที่คั่นด้วย comma
                        $student_id_display = !empty($student_ids) ? implode(', ', $student_ids) : 'ไม่พบรหัสนิสิต';
                        $student_full_name = !empty($student_names) ? implode(',<br>', $student_names) : 'ไม่พบชื่อนิสิต';
                        $advisor_full_name = $row['advisor_full_name'] ?? 'ไม่พบชื่ออาจารย์';
                        $requester_id = $row['requester_id'] ?? 'ไม่ระบุ';

                        echo "<tr>
                                <td>{$row['advisor_request_id']}</td>
                                <td>$student_id_display</td>
                                <td>$student_full_name</td>
                                <td>{$row['advisor_id']}</td>
                                <td>$advisor_full_name</td>
                                <td>{$row['thesis_topic_thai']}</td>
                                <td>{$row['thesis_topic_eng']}</td>
                                <td>{$row['academic_year']}</td>
                                <td>$date</td>
                                <td>$requester_id</td> <!-- แสดง requester_id -->
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' class='no-data'>ไม่มีคำขอที่รอดำเนินการจาก Partner</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        const advisorSelect = new TomSelect("#select-advisors", {
            plugins: ['remove_button'],
            create: false,
            onChange: function() {
                document.getElementById('filterForm').submit();
            }
        });

        const isEvenSelect = new TomSelect("#select-is-even", {
            plugins: ['remove_button'],
            create: false,
            onChange: function() {
                document.getElementById('filterForm').submit();
            }
        });

        const departmentSelect = new TomSelect("#select-department", {
            plugins: ['remove_button'],
            create: false,
            onChange: function() {
                document.getElementById('filterForm').submit();
            }
        });

        document.getElementById('academic_year').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    </script>
</body>

</html>