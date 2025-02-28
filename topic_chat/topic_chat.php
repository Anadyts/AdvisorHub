<?php
session_start();
require('../server.php');
include('../components/navbar.php');
include('render_messages.php');

// จัดการการออกจากระบบ
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
    exit();
}

// ตรวจสอบว่าล็อกอินหรือยัง
if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

// เปลี่ยนหน้าไปโปรไฟล์
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

// ตรวจสอบ receiver_id
if (empty($_SESSION['receiver_id']) || $_SESSION['receiver_id'] == $_SESSION['account_id']) {
    header('location: /AdvisorHub/advisor');
    exit();
}

// จัดการ profileInbox
if (isset($_POST['profileInbox'])) {
    $user_id = $_POST['profileInbox'];
    $_SESSION['profileInbox'] = $user_id;

    $sql = "SELECT role FROM advisor WHERE advisor_id = '$user_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if ($row['role'] == 'advisor') {
        header('location: /AdvisorHub/advisor_profile');
    } else {
        header('location: /AdvisorHub/student_profile');
    }
    exit();
}

// ไม่ให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

// ดึงข้อมูลของ receiver
$receiver_id = $_SESSION['receiver_id'];
$sql = "SELECT advisor_first_name, advisor_last_name FROM advisor WHERE advisor_id = '$receiver_id' 
        UNION 
        SELECT student_first_name, student_last_name FROM student WHERE student_id = '$receiver_id'";
$result = $conn->query($sql);
$receiver = $result->fetch_assoc();

// ตรวจสอบสถานะการอนุมัติ
$id = $_SESSION['account_id'];
$sql = "SELECT COUNT(*) as approved FROM advisor_request 
        WHERE (
            (JSON_CONTAINS(student_id, '\"$id\"') AND advisor_id = '$receiver_id')
            OR 
            (advisor_id = '$id' AND JSON_CONTAINS(student_id, '\"$receiver_id\"'))
        ) 
        AND is_advisor_approved = 1 
        AND is_admin_approved = 1";
$result = $conn->query($sql);
$is_fully_approved = $result->fetch_assoc()['approved'] > 0;

// ดึง timestamp การอนุมัติ
$sql = "SELECT time_stamp FROM advisor_request 
        WHERE (
            (JSON_CONTAINS(student_id, '\"$id\"') AND advisor_id = '$receiver_id')
            OR 
            (advisor_id = '$id' AND JSON_CONTAINS(student_id, '\"$receiver_id\"'))
        ) 
        AND is_advisor_approved = 1 
        AND is_admin_approved = 1 
        ORDER BY time_stamp ASC LIMIT 1";
$result = $conn->query($sql);
$approval_timestamp = $result->num_rows > 0 ? $result->fetch_assoc()['time_stamp'] : null;

$messages_per_page = isset($_GET['results_per_page']) ? (int)$_GET['results_per_page'] : 5; // จำนวนข้อความต่อหน้าเริ่มต้น 5

// ฟังก์ชันดึงข้อความ
function fetchMessages($conn, $id, $receiver_id, $approval_timestamp, $type, $limit)
{
    $where_clause = "WHERE ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                    OR (sender_id = '$receiver_id' AND receiver_id = '$id'))";

    if ($type === 'before' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp <= '$approval_timestamp'";
        $where_clause .= " AND message_title NOT IN (
            SELECT DISTINCT message_title 
            FROM messages 
            WHERE ((sender_id = '$id' AND receiver_id = '$receiver_id') 
                   OR (sender_id = '$receiver_id' AND receiver_id = '$id'))
                   AND time_stamp > '$approval_timestamp'
        )";
    } elseif ($type === 'after' && $approval_timestamp !== null) {
        $where_clause .= " AND time_stamp > '$approval_timestamp'";
    }

    $sql = "
        SELECT message_title, MAX(time_stamp) AS latest_time,
               MAX(message_delete_request) AS delete_request, 
               MAX(message_delete_from_id) AS delete_from_id
        FROM messages
        $where_clause
        GROUP BY message_title
        ORDER BY latest_time DESC
        LIMIT $limit
    ";
    $result = $conn->query($sql);

    $messages = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'title' => $row['message_title'],
                'timestamp' => $row['latest_time'],
                'unread' => $conn->query("SELECT DISTINCT is_read FROM messages 
                                        WHERE receiver_id = '$id' 
                                        AND sender_id = '$receiver_id' 
                                        AND is_read = 0 
                                        AND message_title = '" . $conn->real_escape_string($row['message_title']) . "'")->num_rows > 0,
                'delete_request' => $row['delete_request'],
                'delete_from_id' => $row['delete_from_id']
            ];
        }
    }
    return $messages;
}

// ดึงจำนวนข้อความทั้งหมด
$before_messages_total = count(fetchMessages($conn, $id, $receiver_id, $approval_timestamp, 'before', 9999));
$after_messages_total = count(fetchMessages($conn, $id, $receiver_id, $approval_timestamp, 'after', 9999));

// ฟังก์ชันดึง thesis_id เพื่อแสดงปุ่ม Teams
function getThesisId($conn, $receiver_id, $current_user_id)
{
    $sql = "SELECT advisor_request_id FROM advisor_request 
            WHERE (
                (advisor_id = ? AND JSON_CONTAINS(student_id, ?))
                OR (advisor_id = ? AND JSON_CONTAINS(student_id, ?))
            )
            AND is_advisor_approved = 1 
            AND is_admin_approved = 1 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $student_id_json = json_encode($current_user_id);
    $receiver_id_json = json_encode($receiver_id);
    $stmt->bind_param("ssss", $receiver_id, $student_id_json, $current_user_id, $receiver_id_json);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['advisor_request_id'];
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="assets/css/topic_chat.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        var receiverId = '<?php echo $receiver_id; ?>';
    </script>
    <script src="assets/js/topic_chat.js"></script>
</head>

<body>
    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']); ?>

    <div class='topic-container'>
        <div class='topic-head'>
            <h2><?php echo $receiver['advisor_first_name'] . ' ' . $receiver['advisor_last_name']; ?></h2>
            <div class="topic-head-actions">
                <?php if ($is_fully_approved): ?>
                    <form action="../thesis_resource/thesis_resource.php" method="POST" style="display: inline;">
                        <input type="hidden" name="thesis_id" value="<?php echo htmlspecialchars(getThesisId($conn, $receiver_id, $_SESSION['account_id'])); ?>">
                        <button type="submit" class="thesis-btn fa-solid fa-user-group"></button>
                    </form>
                <?php endif; ?>
                <a href="topic_create.php" class="fa-solid fa-circle-plus"></a>
            </div>
        </div>

        <div class="topic-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search-input" placeholder="Search topic" value="" />
        </div>

        <div class="topic-status">
            <?php if ($is_fully_approved): ?>
                <button class="active" data-section="after">Post-Approval</button>
                <button data-section="before">Pre-Approval</button>
            <?php else: ?>
                <button class="active" data-section="before">Pre-Approval</button>
            <?php endif; ?>
        </div>

        <div class='divider'></div>

        <div id="search-results">
            <!-- After Approval Section -->
            <div class='topic-section after-approve <?php echo $is_fully_approved ? 'active' : ''; ?>' data-section="after">
                <h3>After Becoming an Advisor</h3>
                <div class="message-container" data-type="after">
                    <?php
                    $page_after = isset($_GET['page_after']) ? (int)$_GET['page_after'] : 1;
                    $start_from_after = ($page_after - 1) * $messages_per_page;
                    $after_messages = fetchMessages($conn, $id, $receiver_id, $approval_timestamp, 'after', "$start_from_after, $messages_per_page");
                    echo renderMessages($after_messages, $receiver_id, $after_messages_total, $start_from_after, 'after', '', $conn, $id);

                    $total_pages_after = ceil($after_messages_total / $messages_per_page);

                    // ปรับการคำนวณผลลัพธ์
                    if ($after_messages_total > 0) {
                        $start_result_after = $start_from_after + 1;
                        $end_result_after = min($start_from_after + $messages_per_page, $after_messages_total);
                    } else {
                        $start_result_after = 0;
                        $end_result_after = 0;
                    }

                    if ($total_pages_after > 1) {
                        echo '<div class="pagination">';
                        // Prev button
                        if ($page_after > 1) {
                            echo "<a href='?page_after=" . ($page_after - 1) . "&results_per_page=$messages_per_page' class='pagination-arrow'>«</a>";
                        } else {
                            echo "<a href='#' class='pagination-arrow disabled'>«</a>";
                        }

                        // Page numbers
                        for ($i = 1; $i <= $total_pages_after; $i++) {
                            $active = $i == $page_after ? 'active' : '';
                            echo "<a href='?page_after=$i&results_per_page=$messages_per_page' class='$active' data-page='$i'>$i</a>";
                        }

                        // Next button
                        if ($page_after < $total_pages_after) {
                            echo "<a href='?page_after=" . ($page_after + 1) . "&results_per_page=$messages_per_page' class='pagination-arrow'>»</a>";
                        } else {
                            echo "<a href='#' class='pagination-arrow disabled'>»</a>";
                        }
                        echo '</div>';
                    }
                    ?>
                    <?php if ($after_messages_total > 0): ?>
                        <div class="results-info">
                            Results: <?php echo "$start_result_after - $end_result_after of $after_messages_total messages"; ?>
                            <select class="results-per-page" onchange="changeResultsPerPage(this.value, 'after')">
                                <option value="5" <?php echo $messages_per_page == 5 ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo $messages_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $messages_per_page == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="<?php echo $after_messages_total; ?>" <?php echo $messages_per_page == $after_messages_total ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Before Approval Section -->
            <div class='topic-section before-approve <?php echo !$is_fully_approved ? 'active' : ''; ?>' data-section="before">
                <h3>Before Becoming an Advisor</h3>
                <div class="message-container" data-type="before">
                    <?php
                    $page_before = isset($_GET['page_before']) ? (int)$_GET['page_before'] : 1;
                    $start_from_before = ($page_before - 1) * $messages_per_page;
                    $before_messages = fetchMessages($conn, $id, $receiver_id, $approval_timestamp, 'before', "$start_from_before, $messages_per_page");
                    echo renderMessages($before_messages, $receiver_id, $before_messages_total, $start_from_before, 'before', '', $conn, $id);

                    $total_pages_before = ceil($before_messages_total / $messages_per_page);

                    // ปรับการคำนวณผลลัพธ์
                    if ($before_messages_total > 0) {
                        $start_result_before = $start_from_before + 1;
                        $end_result_before = min($start_from_before + $messages_per_page, $before_messages_total);
                    } else {
                        $start_result_before = 0;
                        $end_result_before = 0;
                    }

                    if ($total_pages_before > 1) {
                        echo '<div class="pagination">';
                        // Prev button
                        if ($page_before > 1) {
                            echo "<a href='?page_before=" . ($page_before - 1) . "&results_per_page=$messages_per_page' class='pagination-arrow'>«</a>";
                        } else {
                            echo "<a href='#' class='pagination-arrow disabled'>«</a>";
                        }

                        // Page numbers
                        for ($i = 1; $i <= $total_pages_before; $i++) {
                            $active = $i == $page_before ? 'active' : '';
                            echo "<a href='?page_before=$i&results_per_page=$messages_per_page' class='$active' data-page='$i'>$i</a>";
                        }

                        // Next button
                        if ($page_before < $total_pages_before) {
                            echo "<a href='?page_before=" . ($page_before + 1) . "&results_per_page=$messages_per_page' class='pagination-arrow'>»</a>";
                        } else {
                            echo "<a href='#' class='pagination-arrow disabled'>»</a>";
                        }
                        echo '</div>';
                    }
                    ?>
                    <?php if ($before_messages_total > 0): ?>
                        <div class="results-info">
                            Results: <?php echo "$start_result_before - $end_result_before of $before_messages_total messages"; ?>
                            <select class="results-per-page" onchange="changeResultsPerPage(this.value, 'before')">
                                <option value="5" <?php echo $messages_per_page == 5 ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo $messages_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $messages_per_page == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="<?php echo $before_messages_total; ?>" <?php echo $messages_per_page == $before_messages_total ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>