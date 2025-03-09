<?php
session_start();
require('../server.php');
include('../components/navbar.php');

// ตรวจสอบว่าเป็นแอดมินและล็อกอินแล้วหรือไม่ ถ้าไม่ใช่ให้เปลี่ยนเส้นทางไปหน้า login
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header('location: /AdvisorHub/login');
    exit();
}

// ตรวจสอบว่ามีการระบุ student_id และ advisor_id ใน URL หรือไม่ ถ้าไม่มีให้กลับไปหน้า index
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id'])) {
    header("Location: index.php");
    exit();
}

// จัดการ logout เมื่อกดปุ่ม logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// รับค่า student_id และ advisor_id จาก URL
$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];

// กำหนดการเรียงลำดับข้อความ (ใหม่ไปเก่า หรือ เก่าไปใหม่) ค่าเริ่มต้นคือ 'newest'
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'newest';
$order_direction = ($sort_order === 'newest') ? 'DESC' : 'ASC';

// กำหนดตัวแปรสำหรับการแบ่งหน้า (pagination)
$results_per_page = isset($_GET['results_per_page']) ? (int)$_GET['results_per_page'] : 20; // จำนวนผลลัพธ์ต่อหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // หน้าปัจจุบัน

// ตรวจสอบสถานะการอนุมัติจากตาราง advisor_request
$sql_approval = "
    SELECT COUNT(*) as approved, MIN(time_stamp) as approval_timestamp 
    FROM advisor_request 
    WHERE advisor_id = ? AND JSON_CONTAINS(student_id, ?)
    AND is_advisor_approved = 1 AND is_admin_approved = 1";
$stmt_approval = $conn->prepare($sql_approval);
$student_id_json = json_encode($student_id);
$stmt_approval->bind_param("ss", $advisor_id, $student_id_json);
$stmt_approval->execute();
$approval_result = $stmt_approval->get_result();
$approval_row = $approval_result->fetch_assoc();
$is_fully_approved = $approval_row['approved'] > 0; // ตรวจสอบว่ามีการอนุมัติครบถ้วนหรือไม่
$approval_timestamp = $is_fully_approved ? $approval_row['approval_timestamp'] : null; // เวลาที่อนุมัติ

// ฟังก์ชันนับจำนวนข้อความตามประเภท (ก่อนหรือหลังการอนุมัติ)
function fetchMessageCount($conn, $student_id, $advisor_id, $approval_timestamp, $type)
{
    $where_clause = "WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
    if ($type === 'before' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp <= ?"; // ข้อความก่อนการอนุมัติ
        $where_clause .= " AND message_title NOT IN (
            SELECT message_title 
            FROM messages 
            WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
            AND time_stamp > ?
        )"; // กรองข้อความที่ไม่ข้ามช่วงการอนุมัติ
    } elseif ($type === 'after' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp > ?"; // ข้อความหลังการอนุมัติ
    }

    $sql = "SELECT COUNT(DISTINCT message_title) as total FROM messages $where_clause";
    $stmt = $conn->prepare($sql);
    if ($type === 'before' && $approval_timestamp !== null) {
        $stmt->bind_param(
            "ssssssssss",
            $student_id,
            $advisor_id,
            $advisor_id,
            $student_id,
            $approval_timestamp, // Main query
            $student_id,
            $advisor_id,
            $advisor_id,
            $student_id,
            $approval_timestamp // Subquery
        );
    } elseif ($type === 'after' && $approval_timestamp !== null) {
        $stmt->bind_param("sssss", $student_id, $advisor_id, $advisor_id, $student_id, $approval_timestamp);
    } else {
        $stmt->bind_param("ssss", $student_id, $advisor_id, $advisor_id, $student_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0 ? $row['total'] : 0; // ส่งคืนจำนวนข้อความ หากไม่มีให้คืน 0
}

// นับจำนวนข้อความก่อนและหลังการอนุมัติ
$before_messages_total = fetchMessageCount($conn, $student_id, $advisor_id, $approval_timestamp, 'before');
$after_messages_total = fetchMessageCount($conn, $student_id, $advisor_id, $approval_timestamp, 'after');

// กำหนดส่วนที่ใช้งานอยู่ (ก่อนหรือหลังการอนุมัติ) ค่าเริ่มต้นขึ้นอยู่กับสถานะการอนุมัติ
$active_section = isset($_GET['section']) ? $_GET['section'] : ($is_fully_approved ? 'after' : 'before');

// คำนวณจำนวนหน้าทั้งหมดในส่วนที่เลือก
$active_total = $active_section === 'before' ? $before_messages_total : $after_messages_total;
$total_pages = ($active_total > 0) ? ceil($active_total / $results_per_page) : 1;

// คำนวณจุดเริ่มต้นของผลลัพธ์
$start_from = max(0, min(($page - 1) * $results_per_page, $active_total));
$start_from = ($active_total > 0) ? $start_from : 0;

// คิวรีเพื่อดึงหัวข้อข้อความ
$sql = "
    SELECT DISTINCT 
        m.message_title,
        MAX(m.time_stamp) as latest_timestamp
    FROM 
        messages m
    WHERE 
        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
";
if ($active_section === 'before' && $approval_timestamp !== null) {
    $sql .= " AND m.time_stamp <= ? 
              AND m.message_title NOT IN (
                  SELECT message_title 
                  FROM messages 
                  WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                  AND time_stamp > ?
              )";
} elseif ($active_section === 'after' && $approval_timestamp !== null) {
    $sql .= " AND m.time_stamp > ?";
}
$sql .= "
    GROUP BY 
        m.message_title
    ORDER BY 
        latest_timestamp $order_direction
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
if ($active_section === 'before' && $approval_timestamp !== null) {
    $stmt->bind_param(
        "ssssssssssii",
        $student_id,
        $advisor_id,
        $advisor_id,
        $student_id,
        $approval_timestamp, // Main query
        $student_id,
        $advisor_id,
        $advisor_id,
        $student_id,
        $approval_timestamp, // Subquery
        $start_from,
        $results_per_page
    );
} elseif ($active_section === 'after' && $approval_timestamp !== null) {
    $stmt->bind_param("sssssii", $student_id, $advisor_id, $advisor_id, $student_id, $approval_timestamp, $start_from, $results_per_page);
} else {
    $stmt->bind_param("ssssii", $student_id, $advisor_id, $advisor_id, $student_id, $start_from, $results_per_page);
}
$stmt->execute();
$result = $stmt->get_result();

// คำนวณช่วงผลลัพธ์ที่แสดง
$start_result = ($active_total > 0) ? ($start_from + 1) : 0;
$end_result = ($active_total > 0) ? min($start_from + $results_per_page, $active_total) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Chat Titles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/view_chat.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // ส่งค่าตัวแปร PHP ไปยัง JavaScript
        const resultsPerPage = <?php echo json_encode($results_per_page); ?>;
        const studentId = <?php echo json_encode($student_id); ?>;
        const advisorId = <?php echo json_encode($advisor_id); ?>;
        const activeSection = '<?php echo $active_section; ?>';
        const totalRecords = <?php echo json_encode($active_total); ?>;
    </script>
    <script src="assets/js/view_chat.js" defer></script>
    <link rel="icon" href="../Logo.png">
</head>

<body>
    <?php renderNavbar(['home', 'advisor', 'statistics']); // แสดงแถบนำทาง 
    ?>

    <div id="chatData" data-student-id="<?php echo htmlspecialchars($student_id); ?>" data-advisor-id="<?php echo htmlspecialchars($advisor_id); ?>" data-total-records="<?php echo htmlspecialchars($active_total); ?>">
        <div class="container">
            <div class="title-header">
                <a href="index.php" class="fa-solid fa-arrow-left"></a> <!-- ปุ่มย้อนกลับ -->
                <h1>Chat Titles</h1> <!-- หัวข้อหน้า -->
            </div>

            <div class="status-sort-container">
                <div class="topic-status">
                    <?php if ($is_fully_approved): // ถ้ามีการอนุมัติครบถ้วน แสดงปุ่มทั้งสอง 
                    ?>
                        <button class="<?php echo $active_section === 'after' ? 'active' : ''; ?>" data-section="after">Post-Approval</button>
                        <button class="<?php echo $active_section === 'before' ? 'active' : ''; ?>" data-section="before">Pre-Approval</button>
                    <?php else: // ถ้ายังไม่มีการอนุมัติ แสดงเฉพาะ Pre-Approval 
                    ?>
                        <button class="active" data-section="before">Pre-Approval</button>
                    <?php endif; ?>
                </div>
                <select id="sortOrder"> <!-- ตัวเลือกการเรียงลำดับ -->
                    <option value="newest" <?php echo $sort_order === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo $sort_order === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                </select>
            </div>

            <div class="divider"></div> <!-- เส้นแบ่ง -->

            <div id="titleContainer">
                <?php
                if (mysqli_num_rows($result) > 0) { // ถ้ามีหัวข้อข้อความ
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                        <div class="title-item" data-timestamp="<?php echo htmlspecialchars($row['latest_timestamp']); ?>">
                            <span><?php echo htmlspecialchars($row['message_title']); ?></span> <!-- แสดงชื่อหัวข้อ -->
                            <button onclick="window.location.href='chat_details.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($row['message_title']); ?>'">View</button> <!-- ปุ่มดูรายละเอียด -->
                        </div>
                <?php
                    }
                } else {
                    echo "<p>No message titles found in this section.</p>"; // ถ้าไม่มีหัวข้อในส่วนนี้
                }
                ?>
            </div>

            <?php if ($active_total > 0 && ceil($active_total / $results_per_page) > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $page - 1; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>&section=<?php echo $active_section; ?>" class="pagination-arrow">«</a>
                    <?php else: ?>
                        <a href="#" class="pagination-arrow disabled">«</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= ceil($active_total / $results_per_page); $i++): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $i; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>&section=<?php echo $active_section; ?>"
                            class="<?php echo $i == $page ? 'active' : ''; ?>"
                            data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < ceil($active_total / $results_per_page)): ?>
                        <a href="?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=<?php echo $page + 1; ?>&results_per_page=<?php echo $results_per_page; ?>&sort_order=<?php echo $sort_order; ?>&section=<?php echo $active_section; ?>" class="pagination-arrow">»</a>
                    <?php else: ?>
                        <a href="#" class="pagination-arrow disabled">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($active_total > 0): ?>
                <div class="results-info">
                    Results: <?php echo "$start_result - $end_result of $active_total titles"; ?>
                    <select class="results-per-page">
                        <option value="20" <?php echo $results_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $results_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $results_per_page == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="<?php echo $active_total; ?>" <?php echo $results_per_page == $active_total ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
$stmt_approval->close();
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>