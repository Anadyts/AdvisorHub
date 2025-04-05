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

// ดึงค่าปีการศึกษาจาก GET
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

// ดึงปีการศึกษาที่มีอยู่ทั้งหมดจากฐานข้อมูล
$yearQuery = "SELECT DISTINCT academic_year FROM advisor_request ORDER BY academic_year DESC";
$yearResult = $conn->query($yearQuery);

// ดึงรายชื่ออาจารย์ทั้งหมดสำหรับตัวเลือกใน select box
$advisorQuery = "SELECT DISTINCT CONCAT(advisor_first_name, ' ', advisor_last_name) AS advisor_full_name 
                 FROM advisor ORDER BY advisor_first_name, advisor_last_name";
$advisorResult = $conn->query($advisorQuery);

// คิวรีข้อมูล โดยจะกรองปีการศึกษาถ้ามีค่า GET
$sql = "SELECT 
            ar.advisor_request_id, 
            ar.student_id,
            ar.advisor_id,
            CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_full_name,
            ar.thesis_topic_thai, 
            ar.thesis_topic_eng, 
            ar.academic_year,
            ar.time_stamp,
            ar.is_even
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        WHERE ar.is_advisor_approved = 0 AND ar.partner_accepted != 2 AND ar.is_admin_approved != 2";

if ($selected_year != null) {
    $sql .= " AND ar.academic_year = '" . $conn->real_escape_string($selected_year) . "'";
}

$sql .= " ORDER BY ar.academic_year DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Pending</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/advisor_pending.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>

<style>
    .advisor-filter-container {
        max-width: 1000px;
        margin: 0 auto 40px auto;
        padding: 30px;
        background: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .advisor-filter-container h6 {
        font-size: 18px;
        font-weight: bold;
        color: #410690;
        margin: 0 0 15px 0;
        text-align: left;
    }

    .advisor-filter-container .ts-wrapper {
        position: relative;
    }

    .advisor-filter-container .ts-wrapper .ts-control {
        border: 2px solid #999 !important;
        border-radius: 8px;
        padding: 10px;
        background-color: #fff;
        font-size: 16px;
        min-height: 40px;
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .advisor-filter-container .ts-control:not(:empty) {
        border-color: #410690 !important;
    }

    .advisor-filter-container .ts-control:focus-within {
        border-color: #6a11cb !important;
        box-shadow: 0 0 8px rgba(106, 17, 203, 0.3);
    }

    .advisor-filter-container select {
        border: 2px solid #999 !important;
        border-radius: 8px;
        padding: 10px;
        background-color: #fff;
        font-size: 16px;
        min-height: 40px;
    }

    .advisor-filter-container .ts-control input {
        border: none !important;
        outline: none;
        background: transparent;
        color: #666;
    }

    .advisor-filter-container .item {
        background-color: #410690;
        color: #fff;
        border-radius: 4px;
        padding: 4px 8px;
        margin: 2px;
        display: inline-flex;
        align-items: center;
    }

    .advisor-filter-container .item .remove {
        margin-left: 6px;
        cursor: pointer;
        color: #fff;
        font-weight: bold;
        font-size: 12px;
    }

    .advisor-filter-container .ts-dropdown {
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
    }

    .advisor-filter-container .option {
        padding: 10px 15px;
        font-size: 16px;
        color: #333;
        cursor: pointer;
    }

    .advisor-filter-container .option:hover,
    .advisor-filter-container .option.active {
        background-color: #f1f1f1;
    }
</style>

<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor', 'statistics']) ?>

    <h2 class="header">รายละเอียดคำขอจาก Advisor ที่รอดำเนินการ</h2>

    <!-- ฟอร์มเลือกปีการศึกษา -->
    <div class="filter-container">
        <form method="GET" class="filter-form">
            <label for="academic_year">ปีการศึกษา:</label>
            <select name="academic_year" id="academic_year" onchange="this.form.submit()">
                <option value="">ทั้งหมด</option>
                <?php
                while ($row = $yearResult->fetch_assoc()) {
                    $year = $row['academic_year'];
                    $selected = ($year == $selected_year) ? 'selected' : '';
                    echo "<option value='$year' $selected>$year</option>";
                }
                ?>
            </select>
        </form>
    </div>

    <!-- ตัวกรองชื่ออาจารย์, ประเภทการทำ, และสาขา -->
    <div class="advisor-filter-container">
        <h6>กรองชื่ออาจารย์</h6>
        <select id="select-advisors" multiple data-placeholder="กรองชื่ออาจารย์" class="form-control">
            <optgroup label="Advisor">
                <?php
                while ($row = $advisorResult->fetch_assoc()) {
                    $advisor_name = htmlspecialchars($row['advisor_full_name'], ENT_QUOTES, 'UTF-8');
                    echo "<option value='$advisor_name'>$advisor_name</option>";
                }
                ?>
            </optgroup>
        </select>

        <h6 style="margin-top: 20px;">กรองประเภทการทำ</h6>
        <select id="select-is-even" multiple data-placeholder="เลือกประเภท (เดี่ยว/คู่)" class="form-control">
            <option value="0">เดี่ยว</option>
            <option value="1">คู่</option>
        </select>

        <h6 style="margin-top: 20px;">กรองสาขา</h6>
        <select id="select-department" multiple data-placeholder="เลือกสาขา" class="form-control">
            <option value="Information Technology">Information Technology</option>
            <option value="Computer Science">Computer Science</option>
        </select>
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
                </tr>
            </thead>
            <tbody id="requestTableBody">
                <?php
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
                } else {
                    echo "<tr><td colspan='9' class='no-data'>ไม่มีคำขอที่รอดำเนินการจาก Advisor</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        // ตัวกรองชื่ออาจารย์
        const advisorSelect = new TomSelect("#select-advisors", {
            plugins: ['remove_button'],
            create: false,
        });

        // ตัวกรองประเภทการทำ (เดี่ยว/คู่)
        const isEvenSelect = new TomSelect("#select-is-even", {
            plugins: ['remove_button'],
            create: false,
        });

        // ตัวกรองสาขา
        const departmentSelect = new TomSelect("#select-department", {
            plugins: ['remove_button'],
            create: false,
        });

        // ฟังก์ชันกรองเมื่อมีการเปลี่ยนแปลงในตัวกรองใดตัวกรองหนึ่ง
        function filterTable() {
            const advisors = advisorSelect.items;
            const isEven = isEvenSelect.items;
            const departments = departmentSelect.items;

            console.log("Selected Advisors:", advisors);
            console.log("Selected is_even:", isEven);
            console.log("Selected Departments:", departments);

            fetch('advisors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'advisors=' + encodeURIComponent(JSON.stringify(advisors)) +
                        '&academic_year=' + encodeURIComponent('<?php echo $selected_year ?? ''; ?>') +
                        '&advisor_pending=1' +
                        '&is_even=' + encodeURIComponent(JSON.stringify(isEven)) +
                        '&departments=' + encodeURIComponent(JSON.stringify(departments))
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Filter Response:", data);
                    document.getElementById('requestTableBody').innerHTML = data;
                });
        }

        // เรียกฟังก์ชันเมื่อมีการเปลี่ยนแปลงในตัวกรอง
        advisorSelect.on('change', filterTable);
        isEvenSelect.on('change', filterTable);
        departmentSelect.on('change', filterTable);
    </script>
</body>

</html>