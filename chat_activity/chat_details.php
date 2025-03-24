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

// Check required parameters
if (!isset($_GET['student_id']) || !isset($_GET['advisor_id']) || !isset($_GET['title'])) {
    header("Location: view_chat.php");
    exit();
}

$student_id = $_GET['student_id'];
$advisor_id = $_GET['advisor_id'];
$message_title = $_GET['title'];

// Pagination variables
$results_per_page = isset($_GET['results_per_page']) ? $_GET['results_per_page'] : 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Count total messages
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
$total_pages = ceil($total_records / $results_per_page);

// Get student and advisor names for reference
$names_sql = "
    SELECT 
        CONCAT(s.student_first_name, ' ', s.student_last_name) AS student_name,
        CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS advisor_name
    FROM 
        student s, advisor a
    WHERE 
        s.student_id = ? AND a.advisor_id = ?
";
$stmt_names = $conn->prepare($names_sql);
$stmt_names->bind_param("ii", $student_id, $advisor_id);
$stmt_names->execute();
$names_result = $stmt_names->get_result();
$names_row = mysqli_fetch_assoc($names_result);
$student_name = $names_row['student_name'];
$advisor_name = $names_row['advisor_name'];

// Main SQL query
$sql = "
    SELECT 
        m.message_id, 
        m.sender_id,
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
        m.time_stamp ASC
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiisii", $student_id, $advisor_id, $advisor_id, $student_id, $message_title, $start_from, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Calculate result range
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
            color: #410690;
        }

        .chat-header {
            background-color: #410690;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            margin: 0;
            font-size: 1.5rem;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 10px;
            position: relative;
            word-wrap: break-word;
        }

        .message-left {
            align-self: flex-start;
            background-color: #e9e9eb;
            color: #333;
            border-bottom-left-radius: 2px;
        }

        .message-right {
            align-self: flex-end;
            background-color: #410690;
            color: white;
            border-bottom-right-radius: 2px;
        }

        .message-info {
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        .message-left .message-info {
            color: #666;
        }

        .message-right .message-info {
            color: #f0f0f0;
            text-align: right;
        }

        .message-content {
            margin: 5px 0;
        }

        .message-file {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px;
            border-radius: 5px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .download-btn {
            padding: 3px 8px;
            background: #fff;
            color: #410690;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .message-right .download-btn {
            background: #fff;
            color: #410690;
        }

        .message-left .download-btn {
            background: #410690;
            color: #fff;
        }

        .download-btn:hover {
            opacity: 0.9;
        }

        .time-stamp {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
        }

        .message-left .time-stamp {
            text-align: left;
        }

        .message-right .time-stamp {
            text-align: right;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #410690;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background: #330572;
        }

        /* Pagination styles */
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
            background-color: #410690;
            color: white;
            border-color: #410690;
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

        .no-messages {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
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
        <div class="chat-header">
            <h2 class="chat-title">ChatDetail - <?php echo htmlspecialchars($message_title); ?></h2>
            <div>
                <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($student_name); ?> - <?php echo htmlspecialchars($advisor_name); ?></span>
            </div>
        </div>
        
        

        <div class="chat-container">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $is_student = ($row['sender_id'] == $student_id);
                    $message_class = $is_student ? 'message-left' : 'message-right';
            ?>
                <div class="message <?php echo $message_class; ?>">
                    <div class="message-info">
                        <?php echo htmlspecialchars($row['sender_name']); ?>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                    </div>
                    <?php if (!empty($row['message_file_name'])) { ?>
                        <div class="message-file">
                            <span><?php echo htmlspecialchars($row['message_file_name']); ?></span>
                            <form action="download_file.php" method="POST" style="display: inline;">
                                <input type="hidden" name="message_id" value="<?php echo $row['message_id']; ?>">
                                <button type="submit" class="download-btn">Download</button>
                            </form>
                        </div>
                    <?php } ?>
                    <div class="time-stamp">
                        <?php echo date('d/m/Y H:i', strtotime($row['time_stamp'])); ?>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<div class='no-messages'>No messages found for this topic</div>";
            }
            ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            // Previous button
            if ($page > 1) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=" . ($page - 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>«</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>«</a>";
            }

            // Page numbers
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

            // Next button
            if ($page < $total_pages) {
                echo "<a href='?student_id=$student_id&advisor_id=$advisor_id&title=" . urlencode($message_title) . "&page=" . ($page + 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>»</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>»</a>";
            }
            ?>
        </div>

        <!-- Results info -->
        <div class="results-info">
            Results: <?php echo $start_result . " - " . $end_result . " of " . $total_records . " messages"; ?>
            <select class="results-per-page" onchange="changeResultsPerPage(this.value)">
                <option value="10" <?php echo $results_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $results_per_page == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $results_per_page == 50 ? 'selected' : ''; ?>>50</option>
                <option value="<?php echo $total_records; ?>" <?php echo $results_per_page == $total_records ? 'selected' : ''; ?>>All</option>
            </select>
        </div>
        <div>
            <a href="view_chat.php?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>" class="back-btn">Back to Chat Title</a>
        </div>
    </div>

    <script>
        function changeResultsPerPage(perPage) {
            const finalPerPage = perPage === "<?php echo $total_records; ?>" ? "<?php echo $total_records; ?>" : perPage;
            window.location.href = `?student_id=<?php echo $student_id; ?>&advisor_id=<?php echo $advisor_id; ?>&title=<?php echo urlencode($message_title); ?>&page=1&results_per_page=${finalPerPage}`;
        }

        // Auto-scroll to bottom of chat on page load
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.querySelector('.chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        });
    </script>
</body>

</html>

<?php
$stmt->close();
$stmt_count->close();
$stmt_names->close();
$conn->close();
?>