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

// รับค่า sortOrder จาก URL ถ้าไม่ระบุใช้ newest เป็นค่าเริ่มต้น
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'newest';
$order_direction = ($sort_order === 'newest') ? 'DESC' : 'ASC'; // กำหนดทิศทางการเรียงลำดับ

// นับจำนวนแถวทั้งหมดในฐานข้อมูล (จำนวนการสนทนาที่ไม่ซ้ำกัน)
$count_sql = "
    SELECT COUNT(DISTINCT LEAST(m.sender_id, m.receiver_id), GREATEST(m.sender_id, m.receiver_id)) as total 
    FROM messages m
    JOIN student s ON s.student_id IN (m.sender_id, m.receiver_id)
    JOIN advisor a ON a.advisor_id IN (m.sender_id, m.receiver_id)
    WHERE s.student_id != a.advisor_id
";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $messages_per_page); // คำนวณจำนวนหน้าทั้งหมด

// คิวรีหลักเพื่อดึงข้อมูลการสนทนา โดยมีการเรียงลำดับตาม sortOrder
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
        <div class="search-filter">
            <input type="text" id="searchInput" placeholder="🔍 Search by user..." onkeyup="filterTable()">
            <!-- Dropdown สำหรับเลือกการเรียงลำดับ คงค่าเดิมตาม sortOrder -->
            <select id="sortOrder" onchange="sortTable()">
                <option value="newest" <?php echo $sort_order === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="oldest" <?php echo $sort_order === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
            </select>
            <button onclick="exportSelectedChats()">📥 Export Selected to CSV</button>
        </div>
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
                <!-- ปุ่มย้อนกลับ รวม sort_order ในลิงก์ -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>" class="pagination-arrow">«</a>
                <?php else: ?>
                    <a href="#" class="pagination-arrow disabled">«</a>
                <?php endif; ?>

                <!-- เลขหน้า รวม sort_order ในลิงก์ -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>"
                        class="<?php echo $i == $page ? 'active' : ''; ?>"
                        data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- ปุ่มถัดไป รวม sort_order ในลิงก์ -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&results_per_page=<?php echo $messages_per_page; ?>&sort_order=<?php echo $sort_order; ?>" class="pagination-arrow">»</a>
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
</body>

</html>