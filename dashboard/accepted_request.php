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
$yearResult = $conn->query("SELECT DISTINCT academic_year FROM advisor_request ORDER BY academic_year DESC");
$advisorResult = $conn->query("SELECT advisor_id, CONCAT(advisor_first_name, ' ', advisor_last_name) AS name FROM advisor");

function getAdvisorOptions($conn)
{
    $res = $conn->query("SELECT advisor_id, CONCAT(advisor_first_name, ' ', advisor_last_name) AS name FROM advisor");
    $html = '';
    while ($row = $res->fetch_assoc()) {
        $html .= "<option value='{$row['advisor_id']}'>{$row['name']}</option>";
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Requests</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/accepted_request.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        .search-form {
            max-width: 1500px;
            padding: 20px;
            /* border: 1px solid #ccc; */
            border-radius: 12px;
            margin: 20px auto;
            font-family: sans-serif;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }

        .form-row label {
            white-space: nowrap;
        }

        .form-row select,
        .form-row input[type="text"] {
            padding: 6px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .form-actions input,
        .form-actions select {
            flex: 1;
            padding: 13px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-actions button {
            padding: 8px 16px;
            font-size: 1rem;
            background-color: rgb(84, 15, 152);
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-actions button:hover {
            background-color: rgb(87, 13, 122);
        }
    </style>
</head>



<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor', 'statistics']) ?>

    <h2 class="header">รายละเอียดคำขอจากนิสิตที่ถูกอนุมัติ</h2>
    <div class="container">

        <!-- ฟอร์มค้นหา -->
        <!-- ฟอร์มค้นหา -->
        <form method="GET" action="" class="search-form">
            <h3>ค้นหาข้อมูลโดย</h3>

            <!-- แถวที่ 1 -->
            <div class="form-row">
                <label><input type="radio" name="search_type" value="all" checked> ทั้งหมด</label>
                <label><input type="radio" name="search_type" value="requester_id"> รหัสผู้ส่งคำขอ</label>
                <label><input type="radio" name="search_type" value="student_id"> รหัสนิสิต</label>
                <label><input type="radio" name="search_type" value="student_name"> ชื่อ-นามสกุลนิสิต</label>
                <label><input type="radio" name="search_type" value="thesis_topic"> หัวข้อวิทยานิพนธ์</label>
                <label><input type="radio" name="search_type" value="advisor_id"> ชื่ออาจารย์</label>

                <label>
                    ปีการศึกษา:
                    <select name="academic_year">
                        <option value="all">ทั้งหมด</option>
                        <?php while ($row = $yearResult->fetch_assoc()): ?>
                            <option value="<?= $row['academic_year'] ?>"><?= $row['academic_year'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>

            <!-- แถวที่ 2 -->
            <div class="form-actions">
                <input type="text" name="search_input" placeholder="กรอกข้อมูลค้นหา" id="search_input">
                <select name="advisor_select" id="advisor_select" style="display: none;">
                    <option value="">-- เลือกอาจารย์ --</option>
                    <?= getAdvisorOptions($conn) ?>
                </select>
                <button type="submit">ค้นหา</button>
            </div>
        </form>


        <?php
        if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['search_type'])) {
            $searchType = $_GET['search_type'] ?? 'all';
            $searchInput = trim($_GET['search_input'] ?? '');
            $advisorSelect = $_GET['advisor_select'] ?? '';
            $academicYear = $_GET['academic_year'] ?? 'all';

            // เงื่อนไขพื้นฐาน
            $where = "ar.partner_accepted = 1 AND ar.is_admin_approved = 1 AND ar.is_advisor_approved = 1";

            // เพิ่มเงื่อนไขปีการศึกษา
            if ($academicYear != 'all') {
                $where .= " AND ar.academic_year = '$academicYear'";
            }

            // ตัวแปรเพื่อตรวจสอบว่ามีข้อมูลค้นหาหรือไม่
            $hasSearchInput = false;

            // ปรับเงื่อนไขตามประเภทการค้นหา
            switch ($searchType) {
                case 'requester_id':
                    if (!empty($searchInput)) {
                        $where .= " AND ar.requester_id = '$searchInput'";
                        $hasSearchInput = true;
                    }
                    break;
                case 'student_id':
                    if (!empty($searchInput)) {
                        $where .= " AND JSON_CONTAINS(ar.student_id, '\"$searchInput\"')";
                        $hasSearchInput = true;
                    }
                    break;
                case 'student_name':
                    if (!empty($searchInput)) {
                        $where .= " AND EXISTS (
                    SELECT 1 FROM student s 
                    WHERE JSON_CONTAINS(ar.student_id, CONCAT('\"', s.student_id, '\"'))
                    AND CONCAT(s.student_first_name, ' ', s.student_last_name) LIKE '%$searchInput%'
                )";
                        $hasSearchInput = true;
                    }
                    break;
                case 'thesis_topic':
                    if (!empty($searchInput)) {
                        $where .= " AND (ar.thesis_topic_thai LIKE '%$searchInput%' OR ar.thesis_topic_eng LIKE '%$searchInput%')";
                        $hasSearchInput = true;
                    }
                    break;
                case 'advisor_id':
                    if (!empty($advisorSelect)) {
                        $where .= " AND ar.advisor_id = '$advisorSelect'";
                        $hasSearchInput = true;
                    }
                    break;
                case 'all':
                    $hasSearchInput = true; // กรณี "ทั้งหมด" ไม่ต้องกรอกข้อมูลก็แสดงได้
                    break;
                default:
                    break;
            }

            // ถ้าไม่ใช่ "all" และไม่มีข้อมูลค้นหา ให้ข้ามการ query และแสดง "ไม่พบข้อมูล"
            if ($searchType !== 'all' && !$hasSearchInput) {
                echo '<h3>ผลการค้นหา</h3>';
                echo '<table border="1" cellpadding="5" cellspacing="0" class="table-container">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>รหัสคำขอ</th><th>รหัสนิสิต</th><th>ชื่อนิสิต</th><th>รหัสอาจารย์</th><th>ชื่ออาจารย์</th><th>หัวข้อวิทยานิพนธ์ (ไทย)</th><th>หัวข้อวิทยานิพนธ์ (อังกฤษ)</th><th>ปีการศึกษา</th><th>วันที่ร้องขอ</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                echo '<tr><td colspan="9" style="text-align:center; color: gray;">ไม่พบข้อมูล</td></tr>';
                echo '</tbody>';
                echo '</table>';
            } else {
                // Query หลัก
                $query = "SELECT 
            ar.advisor_request_id,
            ar.student_id,  
            a.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_name,
            ar.thesis_topic_thai,
            ar.thesis_topic_eng,
            ar.academic_year,
            ar.time_stamp
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE $where";

                $result = $conn->query($query);
        ?>

                <h3>ผลการค้นหา</h3>
                <table border="1" cellpadding="5" cellspacing="0" class="table-container">
                    <thead>
                        <tr>
                            <th>รหัสคำขอ</th>
                            <th>รหัสนิสิต</th>
                            <th>ชื่อนิสิต</th>
                            <th>รหัสอาจารย์</th>
                            <th>ชื่ออาจารย์</th>
                            <th>หัวข้อวิทยานิพนธ์ (ไทย)</th>
                            <th>หัวข้อวิทยานิพนธ์ (อังกฤษ)</th>
                            <th>ปีการศึกษา</th>
                            <th>วันที่ร้องขอ</th>
                        </tr>
                    </thead>
                    <tbody id="requestTableBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                $studentIds = json_decode($row['student_id'] ?? '[]', true);
                                $studentIdText = is_array($studentIds) ? implode(", ", $studentIds) : '';

                                $studentNames = [];
                                foreach ($studentIds as $sid) {
                                    $studentQuery = $conn->query("SELECT student_first_name, student_last_name FROM student WHERE student_id = '$sid'");
                                    if ($studentRow = $studentQuery->fetch_assoc()) {
                                        $studentNames[] = $studentRow['student_first_name'] . ' ' . $studentRow['student_last_name'];
                                    }
                                }
                                $studentNameText = implode("<br>", $studentNames);
                                ?>
                                <tr>
                                    <td><?= $row['advisor_request_id'] ?></td>
                                    <td><?= $studentIdText ?></td>
                                    <td><?= $studentNameText ?></td>
                                    <td><?= $row['advisor_id'] ?></td>
                                    <td><?= $row['advisor_name'] ?></td>
                                    <td><?= $row['thesis_topic_thai'] ?></td>
                                    <td><?= $row['thesis_topic_eng'] ?></td>
                                    <td><?= $row['academic_year'] ?></td>
                                    <td><?= $row['time_stamp'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color: gray;">ไม่พบข้อมูล</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

        <?php
            }
        }
        ?>



    </div>

</body>
<script>
    const radios = document.querySelectorAll('input[name="search_type"]');
    const input = document.getElementById('search_input');
    const select = document.getElementById('advisor_select');

    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'advisor_id') {
                input.style.display = 'none';
                select.style.display = 'block';
            } else if (radio.value === 'all') {
                input.style.display = 'none';
                select.style.display = 'none';
            } else {
                input.style.display = 'block';
                select.style.display = 'none';
            }
        });
    });

    // เรียกใช้ครั้งแรกเพื่อจัดให้ input/select ซ่อนให้เหมาะกับค่าที่ถูกเลือกตอนโหลดหน้า
    window.addEventListener("DOMContentLoaded", () => {
        const checked = document.querySelector('input[name="search_type"]:checked');
        checked.dispatchEvent(new Event('change'));
    });
</script>

</html>