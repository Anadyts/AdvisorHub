<?php
session_start();
require('../server.php');
include('../components/navbar.php');

if (isset($_SESSION['username']) && $_SESSION['role'] != 'admin' || empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

// ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นครบถ้วนหรือไม่
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id']) || !isset($_GET['title'])) {
    header("Location: view_chat.php");
    exit();
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];
$message_title = $_GET['title'];

// Pagination variables
$results_per_page = isset($_GET['results_per_page']) ? $_GET['results_per_page'] : 10; // จำนวนผลลัพธ์ต่อหน้า (ค่าเริ่มต้น 10)
$page = isset($_GET['page']) ? $_GET['page'] : 1; // หน้าปัจจุบัน
$start_from = ($page - 1) * $results_per_page; // จุดเริ่มต้นของข้อมูล

// คำสั่ง SQL เพื่อนับจำนวนข้อความทั้งหมด
$count_sql = "
    SELECT COUNT(*) as total 
    FROM messages m
    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
    AND m.message_title = ?
";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param("iiiis", $student_id, $advisor_id, $advisor_id, $student_id, $message_title);
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $results_per_page); // คำนวณจำนวนหน้าทั้งหมด

// คำสั่ง SQL หลักพร้อม LIMIT
$sql = "
    SELECT 
        m.message_id, 
        m.message_title, 
        m.message, 
        m.message_file_name, 
        m.message_file_type,
        m.time_stamp, 
        CASE 
            WHEN m.sender_id = s.student_id THEN CONCAT(s.student_first_name, ' ', s.student_last_name)
            WHEN m.sender_id = a.advisor_id THEN CONCAT(a.advisor_first_name, ' ', a.advisor_last_name)
        END AS sender_name 
    FROM 
        messages m
    LEFT JOIN 
        student s ON s.student_id = m.sender_id OR s.student_id = m.receiver_id 
    LEFT JOIN 
        advisor a ON a.advisor_id = m.sender_id OR a.advisor_id = m.receiver_id 
    WHERE 
        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.message_title = ? 
    ORDER BY 
        m.time_stamp DESC 
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiisii", $student_id, $advisor_id, $advisor_id, $student_id, $message_title, $start_from, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

// คำนวณช่วงผลลัพธ์ (เช่น 1-10)
$start_result = ($page - 1) * $results_per_page + 1;
$end_result = min($page * $results_per_page, $total_records);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Details - <?php echo htmlspecialchars($message_title); ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../Logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .message {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .message span {
            font-weight: bold;
        }

        .message p {
            margin: 5px 0;
        }

        .download-btn {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .download-btn:hover {
            background: #0056b3;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #ccc;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background: #bbb;
        }

        /* Pagination styles จาก index.php */
        .pagination {
            margin: 20px 0;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            background-color: white;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination a.disabled {
            color: #ccc;
            pointer-events: none;
            background-color: #f5f5f5;
        }

        .pagination-arrow {
            font-size: 16px;
            font-weight: bold;
        }

        .pagination-ellipsis {
            padding: 8px 12px;
            color: #666;
        }

        .results-info {
            margin: 20px 0;
            text-align: center;
            color: #333;
            font-size: 14px;
        }

        .results-per-page {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-left: 10px;
            font-size: 14px;
        }
    </style>
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

    <div class="container">
        <h1>Chat Details - <?php echo htmlspecialchars($message_title); ?></h1>
        <a href="view_chat.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>" class="back-btn">Back to Chat Title</a>

        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
        ?>
                <div class="message">
                    <span><?php echo htmlspecialchars($row['sender_name']); ?> - <?php echo htmlspecialchars($row['time_stamp']); ?></span>
                    <p><strong>ข้อความ:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                    <?php if (!empty($row['message_file_name'])) { ?>
                        <p><strong>ไฟล์:</strong> <?php echo htmlspecialchars($row['message_file_name']); ?> (<?php echo htmlspecialchars($row['message_file_type']); ?>)
                        <form action="download_file.php" method="POST" style="display: inline;">
                            <input type="hidden" name="message_id" value="<?php echo $row['message_id']; ?>">
                            <button type="submit" class="download-btn">ดาวน์โหลด</button>
                        </form>
                        </p>
                    <?php } ?>
                </div>
        <?php
            }
        } else {
            echo "<p>ไม่พบข้อความสำหรับหัวข้อนี้</p>";
        }
        ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            // ปุ่ม Previous
            if ($page > 1) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=" . ($page - 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>«</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>«</a>";
            }

            // แสดงหมายเลขหน้า
            $max_pages_to_show = 5;
            $half_pages = floor($max_pages_to_show / 2);
            $start_page = max(1, $page - $half_pages);
            $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

            if ($end_page - $start_page + 1 < $max_pages_to_show) {
                $start_page = max(1, $end_page - $max_pages_to_show + 1);
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=$i&results_per_page=$results_per_page' class='pagination-number $active'>$i</a>";
            }

            if ($end_page < $total_pages) {
                echo "<span class='pagination-ellipsis'>...</span>";
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=$total_pages&results_per_page=$results_per_page' class='pagination-number'>$total_pages</a>";
            }

            // ปุ่ม Next
            if ($page < $total_pages) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=" . ($page + 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>»</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>»</a>";
            }
            ?>
        </div>

        <!-- แสดงข้อมูลผลลัพธ์ -->
        <div class="results-info">
            Results: <?php echo $start_result . " - " . $end_result . " of " . $total_records . " messages"; ?>
            <select class="results-per-page" onchange="changeResultsPerPage(this.value)">
                <option value="10" <?php echo $results_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $results_per_page == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $results_per_page == 50 ? 'selected' : ''; ?>>50</option>
                <option value="<?php echo $total_records; ?>" <?php echo $results_per_page == $total_records ? 'selected' : ''; ?>>All</option>
            </select>
        </div>
    </div>

    <script>
        function changeResultsPerPage(perPage) {
            const finalPerPage = perPage === "<?php echo $total_records; ?>" ? "<?php echo $total_records; ?>" : perPage;
            window.location.href = `?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($message_title); ?>&page=1&results_per_page=${finalPerPage}`;
        }
    </script>
</body>

</html>

<?php
$stmt->close();
$stmt_count->close();
$conn->close();
?>