<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin' || empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

// ตรวจสอบว่า student_id และ advisor_id ถูกระบุมาหรือไม่
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];

// รับค่า sortOrder จาก URL (default เป็น newest)
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'newest';
$order_direction = ($sort_order === 'newest') ? 'DESC' : 'ASC'; // กำหนดทิศทางการเรียงลำดับ

// Pagination variables
$results_per_page = isset($_GET['results_per_page']) ? (int)$_GET['results_per_page'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// คำสั่งคิวรี่เพื่อนับจำนวนหัวข้อทั้งหมด
$count_sql = "
    SELECT COUNT(DISTINCT m.message_title) as total 
    FROM messages m
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param("iiii", $student_id, $advisor_id, $advisor_id, $student_id);
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// คำสั่งคิวรี่หลักพร้อม LIMIT และเรียงลำดับตาม sortOrder
$sql = "
    SELECT DISTINCT 
        m.message_title,
        MAX(m.time_stamp) as latest_timestamp
    FROM 
        messages m
    WHERE 
        (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    GROUP BY 
        m.message_title
    ORDER BY 
        latest_timestamp $order_direction
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $student_id, $advisor_id, $advisor_id, $student_id, $start_from, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

// คำนวณช่วงผลลัพธ์
$start_result = ($page - 1) * $results_per_page + 1;
$end_result = min($page * $results_per_page, $total_records);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Chat Titles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/view_chat.css">
    <!-- ส่งค่า PHP ไปยัง JavaScript -->
    <script>
        const resultsPerPage = <?php echo json_encode($results_per_page); ?>;
        const studentId = <?php echo json_encode($student_id); ?>;
        const advisorId = <?php echo json_encode($advisor_id); ?>;
    </script>
    <script src="assets/js/view_chat.js" defer></script>
    <link rel="icon" href="../Logo.png">
</head>

<body>
    <?php
    if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'inbox', 'statistics', 'Teams']);
    } elseif (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
        renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
    } else {
        renderNavbar(allowedPages: ['home', 'login', 'advisor', 'statistics']);
    }
    ?>

    <div id="chatData" data-student-id="<?php echo htmlspecialchars($student_id); ?>" data-advisor-id="<?php echo htmlspecialchars($advisor_id); ?>" data-total-records="<?php echo htmlspecialchars($total_records); ?>">
        <div class="container">
            <h1>Chat Titles</h1>
            <div class="header-container">
                <a href="index.php" class="back-btn">Back to Chat Management</a>
                <!-- คงค่า sortOrder เดิมจาก URL -->
                <select id="sortOrder">
                    <option value="newest" <?php echo $sort_order === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo $sort_order === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                </select>
            </div>

            <div id="titleContainer">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                        <div class="title-item" data-timestamp="<?php echo htmlspecialchars($row['latest_timestamp']); ?>">
                            <span><?php echo htmlspecialchars($row['message_title']); ?></span>
                            <button onclick="window.location.href='chat_details.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($row['message_title']); ?>'">View</button>
                        </div>
                <?php
                    }
                } else {
                    echo "<p>No message titles found between this student and advisor.</p>";
                }
                ?>
            </div>

            <!-- Pagination รวม sort_order ในลิงก์ -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $page - 1; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>" class="pagination-arrow">«</a>
                    <?php else: ?>
                        <a href="#" class="pagination-arrow disabled">«</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $i; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>"
                            class="<?php echo $i == $page ? 'active' : ''; ?>"
                            data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $page + 1; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>" class="pagination-arrow">»</a>
                    <?php else: ?>
                        <a href="#" class="pagination-arrow disabled">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($total_records > 0): ?>
                <div class="results-info">
                    Results: <?php echo $start_result . " - " . $end_result . " of " . $total_records . " titles"; ?>
                    <select class="results-per-page">
                        <option value="20" <?php echo $results_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $results_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $results_per_page == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="<?php echo $total_records; ?>" <?php echo $results_per_page == $total_records ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
$stmt_count->close();
$conn->close();
?>