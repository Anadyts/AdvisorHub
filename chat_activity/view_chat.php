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
    header("Location: index.php"); // เปลี่ยนไปยัง index.php
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];

// Pagination variables
$results_per_page = isset($_GET['results_per_page']) ? $_GET['results_per_page'] : 10; // จำนวนผลลัพธ์ต่อหน้า (ค่าเริ่มต้น 10)
$page = isset($_GET['page']) ? $_GET['page'] : 1; // หน้าปัจจุบัน
$start_from = ($page - 1) * $results_per_page; // จุดเริ่มต้นของข้อมูล

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
$total_pages = ceil($total_records / $results_per_page); // คำนวณจำนวนหน้าทั้งหมด

// คำสั่งคิวรี่หลักพร้อม LIMIT
$sql = "
    SELECT DISTINCT 
        m.message_title
    FROM 
        messages m
    WHERE 
        (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY 
        m.message_title DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $student_id, $advisor_id, $advisor_id, $student_id, $start_from, $results_per_page);
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
    <title>View Chat Titles</title>
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

        .title-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .title-item span {
            font-weight: bold;
        }

        .title-item button {
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .title-item button:hover {
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
        <h1>Chat Titles</h1>
        <a href="index.php" class="back-btn">Back to Chat Management</a>

        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
        ?>
                <div class="title-item">
                    <span><?php echo htmlspecialchars($row['message_title']); ?></span>
                    <button onclick="window.location.href='chat_details.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($row['message_title']); ?>'">View</button>
                </div>
        <?php
            }
        } else {
            echo "<p>No message titles found between this student and advisor.</p>";
        }
        ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            // ปุ่ม Previous
            if ($page > 1) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&page=" . ($page - 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>«</a>";
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
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&page=$i&results_per_page=$results_per_page' class='pagination-number $active'>$i</a>";
            }

            if ($end_page < $total_pages) {
                echo "<span class='pagination-ellipsis'>...</span>";
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&page=$total_pages&results_per_page=$results_per_page' class='pagination-number'>$total_pages</a>";
            }

            // ปุ่ม Next
            if ($page < $total_pages) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&page=" . ($page + 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>»</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>»</a>";
            }
            ?>
        </div>

        <!-- แสดงข้อมูลผลลัพธ์ -->
        <div class="results-info">
            Results: <?php echo $start_result . " - " . $end_result . " of " . $total_records . " titles"; ?>
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
            window.location.href = `?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&page=1&results_per_page=${finalPerPage}`;
        }
    </script>
</body>

</html>

<?php
$stmt->close();
$stmt_count->close();
$conn->close();
?>