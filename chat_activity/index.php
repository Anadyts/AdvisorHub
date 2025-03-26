<?php
session_start();
require('../server.php');
include('../components/navbar.php');

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin หรือไม่ ถ้าไม่ใช่ให้ redirect ไปหน้า login
if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin' || empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

// จัดการ logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// ตัวแปรสำหรับ pagination
$messages_per_page = isset($_GET['results_per_page']) ? (int)$_GET['results_per_page'] : 20; // ค่าเริ่มต้น 20
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // หน้าเริ่มต้นคือ 1
$start_from = ($page - 1) * $messages_per_page; // คำนวณจุดเริ่มต้นของข้อมูล

// รับค่า sortOrder และตัวกรองจาก URL
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'newest';
$order_direction = ($sort_order === 'newest') ? 'DESC' : 'ASC'; // กำหนดทิศทางการเรียงลำดับ
$year_filter = isset($_GET['Year']) ? mysqli_real_escape_string($conn, $_GET['Year']) : '';
$department_filter = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';

// Debug: ตรวจสอบว่าค่า Year และ department ถูกส่งมาหรือไม่
// echo "Year Filter: " . $year_filter . "<br>";
// echo "Department Filter: " . $department_filter . "<br>";

// ดึงข้อมูล department ที่ไม่ซ้ำกันจากตาราง student
$dept_sql = "SELECT DISTINCT student_department FROM student WHERE student_department IS NOT NULL ORDER BY student_department ASC";
$dept_result = mysqli_query($conn, $dept_sql);
$departments = [];
if ($dept_result && mysqli_num_rows($dept_result) > 0) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $departments[] = $row['student_department'];
    }
}

// นับจำนวนแถวทั้งหมดในฐานข้อมูล (จำนวนการสนทนาที่ไม่ซ้ำกัน) พร้อมตัวกรอง
$count_sql = "
    SELECT COUNT(DISTINCT LEAST(m.sender_id, m.receiver_id), GREATEST(m.sender_id, m.receiver_id)) as total 
    FROM messages m
    JOIN student s ON s.student_id IN (m.sender_id, m.receiver_id)
    JOIN advisor a ON a.advisor_id IN (m.sender_id, m.receiver_id)
    WHERE s.student_id != a.advisor_id
";
if (!empty($year_filter)) {
    $count_sql .= " AND YEAR(m.time_stamp) = '$year_filter'";
}
if (!empty($department_filter)) {
    $count_sql .= " AND s.student_department = '$department_filter'";
}
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $messages_per_page); // คำนวณจำนวนหน้าทั้งหมด

// คิวรีหลักเพื่อดึงข้อมูลการสนทนา โดยมีการเรียงลำดับตาม sortOrder และตัวกรอง
$sql = "
    SELECT DISTINCT
        s.student_id,
        CONCAT(s.student_first_name, ' ', s.student_last_name) AS student_name,
        a.advisor_id,
        CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_name,
        MAX(m.time_stamp) AS latest_timestamp
    FROM
        messages m
    JOIN
        student s ON s.student_id IN (m.sender_id, m.receiver_id)
    JOIN
        advisor a ON a.advisor_id IN (m.sender_id, m.receiver_id)
    WHERE
        s.student_id != a.advisor_id
";
if (!empty($year_filter)) {
    $sql .= " AND YEAR(m.time_stamp) = '$year_filter'";
}
if (!empty($department_filter)) {
    $sql .= " AND s.student_department = '$department_filter'";
}
$sql .= "
    GROUP BY
        LEAST(m.sender_id, m.receiver_id),
        GREATEST(m.sender_id, m.receiver_id),
        s.student_id,
        a.advisor_id,
        student_name,
        advisor_name
    ORDER BY
        latest_timestamp $order_direction
    LIMIT $start_from, $messages_per_page
";

$result = mysqli_query($conn, $sql);

// คำนวณช่วงผลลัพธ์ที่แสดงในหน้า
$start_result = $start_from + 1;
$end_result = min($start_from + $messages_per_page, $total_records);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat Management</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="assets/js/index.js" defer></script>
    <link rel="icon" href="../Logo.png">
    <script>
        // ส่งค่า messages_per_page จาก PHP ไปยัง JavaScript
        const messagesPerPage = <?php echo json_encode($messages_per_page); ?>;
    </script>
</head>

<body>
    <?php
    // แสดง navbar ตามบทบาทของผู้ใช้
    if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } elseif (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>
    <div class="container">
        <h1>Admin Chat Management</h1>
        <!-- เพิ่มฟอร์มเพื่อส่งข้อมูลตัวกรอง -->
        <form method="GET" action="">
            <div class="search-filter">
                <input type="text" id="searchInput" placeholder="🔍 Search by user..." onkeyup="filterTable()">
                <!-- Dropdown สำหรับเลือกการเรียงลำดับ คงค่าเดิมตาม sortOrder -->
                <select id="sortOrder" name="sort_order" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort_order === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo $sort_order === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                </select>
                <!-- ตัวกรองปี -->
                <select id="Year" name="Year" onchange="this.form.submit()">
                    <option value="">Year</option>
                    <?php
                    $years = ['2025', '2024', '2023', '2022', '2021']; // แก้ไขตามปีที่มีในฐานข้อมูล
                    foreach ($years as $year) {
                        $selected = ($year_filter == $year) ? 'selected' : '';
                        echo "<option value=\"$year\" $selected>$year</option>";
                    }
                    ?>
                </select>
                <!-- ตัวกรองสาขา -->
                <select id="department" name="department" onchange="this.form.submit()">
                    <option value="">Department</option>
                    <?php
                    foreach ($departments as $dept) {
                        $selected = ($department_filter == $dept) ? 'selected' : '';
                        echo "<option value=\"$dept\" $selected>$dept</option>";
                    }
                    ?>
                </select>
                <button onclick="exportSelectedChats()">📥 Export Selected to CSV</button>
                <!-- ซ่อน input สำหรับ pagination เพื่อคงค่า -->
                <input type="hidden" name="page" value="<?php echo $page; ?>">
                <input type="hidden" name="results_per_page" value="<?php echo $messages_per_page; ?>">
            </div>
        </form>
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()">All</th>
                    <th>Student</th>
                    <th>Advisor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="chatTable">
                <?php
                // แสดงข้อมูลในตาราง ถ้ามีผลลัพธ์
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr data-timestamp='" . htmlspecialchars($row['latest_timestamp']) . "'>";
                        echo "<td><input type='checkbox' class='chatCheckbox' data-student-id='" . $row['student_id'] . "' data-advisor-id='" . $row['advisor_id'] . "'></td>";
                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['advisor_name']) . "</td>";
                        echo "<td><a href='view_chat.php?student_id=" . $row['student_id'] . "&advisor_id=" . $row['advisor_id'] . "'>View</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No chats found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- ปุ่มย้อนกลับ รวม sort_order และตัวกรองในลิงก์ -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>&Year=<?php echo $year_filter; ?>&department=<?php echo $department_filter; ?>" class="pagination-arrow">«</a>
                <?php else: ?>
                    <a href="#" class="pagination-arrow disabled">«</a>
                <?php endif; ?>

                <!-- เลขหน้า รวม sort_order และตัวกรองในลิงก์ -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>&Year=<?php echo $year_filter; ?>&department=<?php echo $department_filter; ?>"
                        class="<?php echo $i == $page ? 'active' : ''; ?>"
                        data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- ปุ่มถัดไป รวม sort_order และตัวกรองในลิงก์ -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>&Year=<?php echo $year_filter; ?>&department=<?php echo $department_filter; ?>" class="pagination-arrow">»</a>
                <?php else: ?>
                    <a href="#" class="pagination-arrow disabled">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($total_records > 0): ?>
            <div class="results-info">
                Results: <?php echo "$start_result - $end_result of $total_records messages"; ?>
                <!-- Dropdown จำนวนผลลัพธ์ต่อหน้า -->
                <select class="results-per-page" onchange="changeResultsPerPage(this.value)">
                    <option value="20" <?php echo $messages_per_page == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $messages_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $messages_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="<?php echo $total_records; ?>" <?php echo $messages_per_page == $total_records ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function changeResultsPerPage(perPage) {
            window.location.href = `?page=1&results_per_page=${perPage}&sort_order=<?php echo $sort_order; ?>&Year=<?php echo $year_filter; ?>&department=<?php echo $department_filter; ?>`;
        }

        // JavaScript เดิมสำหรับการกรองและเรียงลำดับในตาราง
        const tbody = document.getElementById('chatTable');
        let originalRows = Array.from(tbody.getElementsByTagName('tr'));

        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const rows = originalRows.slice();

            const filteredRows = rows.filter(row => {
                const studentName = row.cells[1].textContent.toLowerCase();
                const advisorName = row.cells[2].textContent.toLowerCase();
                return studentName.includes(searchInput) || advisorName.includes(searchInput);
            });

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }

            const pageRows = filteredRows.slice(0, messagesPerPage);
            pageRows.forEach(row => tbody.appendChild(row));

            sortTable();
        }

        function sortTable() {
            const sortOrder = document.getElementById('sortOrder').value;
            const rows = Array.from(tbody.getElementsByTagName('tr'));

            rows.sort((a, b) => {
                const timeA = new Date(a.getAttribute('data-timestamp'));
                const timeB = new Date(b.getAttribute('data-timestamp'));
                return sortOrder === 'newest' ? timeB - timeA : timeA - timeB;
            });

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            rows.forEach(row => tbody.appendChild(row));
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll').checked;
            const checkboxes = document.getElementsByClassName('chatCheckbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = selectAll;
            }
        }

        function exportSelectedChats() {
            const checkboxes = document.getElementsByClassName('chatCheckbox');
            const selectedPairs = [];

            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    const studentId = checkbox.getAttribute('data-student-id');
                    const advisorId = checkbox.getAttribute('data-advisor-id');
                    selectedPairs.push({
                        student_id: studentId,
                        advisor_id: advisorId
                    });
                }
            }

            if (selectedPairs.length === 0) {
                alert('Please select at least one chat to export.');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_chat.php';
            form.style.display = 'none';

            const pairsInput = document.createElement('input');
            pairsInput.type = 'hidden';
            pairsInput.name = 'selected_pairs';
            pairsInput.value = JSON.stringify(selectedPairs);
            form.appendChild(pairsInput);

            document.body.appendChild(form);
            form.submit();
        }

        window.onload = function() {
            originalRows = Array.from(tbody.getElementsByTagName('tr'));
            sortTable();
        }
    </script>
</body>

</html>