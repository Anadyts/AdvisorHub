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

// Pagination variables
$results_per_page = isset($_GET['results_per_page']) ? $_GET['results_per_page'] : 10; // Number of results per page (default 10)
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page
$start_from = ($page - 1) * $results_per_page; // Starting point of data

// Query to count total records
$count_sql = "
    SELECT COUNT(DISTINCT LEAST(m.sender_id, m.receiver_id), GREATEST(m.sender_id, m.receiver_id)) as total 
    FROM messages m
    JOIN student s ON s.student_id IN (m.sender_id, m.receiver_id)
    JOIN advisor a ON a.advisor_id IN (m.sender_id, m.receiver_id)
    WHERE s.student_id != a.advisor_id
";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $results_per_page); // Calculate total pages

// Main query with LIMIT
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
        latest_timestamp DESC
    LIMIT $start_from, $results_per_page
";

$result = mysqli_query($conn, $sql);

// Calculate result range (e.g., 1-10)
$start_result = ($page - 1) * $results_per_page + 1;
$end_result = min($page * $results_per_page, $total_records);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat Management</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="icon" href="../Logo.png">
    <style>
        /* รูปแบบพื้นฐานของหน้าเว็บ */
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(255, 255, 255);
            margin: 0;
        }

        /* รูปแบบของ container */
        .container {
            max-width: 900px;
            margin: auto;
            margin-top: 2rem;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgb(136, 134, 134);
        }

        /* รูปแบบของหัวข้อ */
        .container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        /* รูปแบบของแถบค้นหาและกรอง */
        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }

        /* รูปแบบของ input, select และ button */
        .container input,
        .container select,
        .container button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        /* รูปแบบเมื่อ input และ select ถูกโฟกัส */
        .container input:focus,
        .container select:focus {
            border-color: #007bff;
            outline: none;
        }

        /* รูปแบบของปุ่ม */
        .container button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        /* รูปแบบเมื่อเมาส์ชี้ที่ปุ่ม */
        .container button:hover {
            background: #0056b3;
        }

        /* รูปแบบของตาราง */
        .container table {
            width: 100%;
            border-collapse: collapse;
            /* รวมเส้นขอบ */
            background: white;
            table-layout: fixed;
            /* บังคับให้ตารางมีโครงสร้างขนาดคงที่เพื่อรักษาความกว้างของคอลัมน์ */
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* รูปแบบของส่วนหัวและเซลล์ในตาราง */
        .container th,
        .container td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            overflow: hidden;
            /* ซ่อนข้อความที่ล้น */
            text-overflow: ellipsis;
            /* แสดง ... สำหรับข้อความที่ล้น */
            white-space: nowrap;
            /* ป้องกันข้อความห่อกลับ */
        }

        /* รูปแบบของส่วนหัวตาราง */
        .container th {
            background: #007bff;
            color: white;
            font-weight: bold;
            /* ทำให้ส่วนหัวตารางหนาขึ้น */
        }

        /* รูปแบบของแถวคี่ */
        .container tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* ปรับความกว้างของคอลัมน์สำหรับ All (checkbox) และ Action (View) */
        .container th:nth-child(1),
        /* คอลัมน์ All (checkbox) */
        .container td:nth-child(1) {
            width: 5%;
            /* ความกว้างแคบสำหรับ checkbox */
            padding: 8px;
            /* ลด padding เพื่อขนาดที่เล็กลง */
        }

        .container th:nth-child(2),
        /* คอลัมน์ Student */
        .container td:nth-child(2) {
            width: 35%;
            /* เพิ่มความกว้างเพื่อสมดุลกับคอลัมน์อื่น */
        }

        .container th:nth-child(3),
        /* คอลัมน์ Advisor */
        .container td:nth-child(3) {
            width: 35%;
            /* เพิ่มความกว้างเพื่อสมดุลกับคอลัมน์อื่น */
        }

        .container th:nth-child(4),
        /* คอลัมน์ Action (View) */
        .container td:nth-child(4) {
            width: 5%;
            /* ความกว้างแคบสำหรับลิงก์ Action/View */
            padding: 8px;
            /* ลด padding เพื่อขนาดที่เล็กลง */
        }

        /* รูปแบบของลิงก์ */
        .container a {
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s ease;
        }

        /* รูปแบบเมื่อเมาส์ชี้ที่ลิงก์ */
        .container a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        /* รูปแบบของการแบ่งหน้า */
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
            border-color: #ddd;
        }

        .pagination-arrow {
            font-size: 16px;
            font-weight: bold;
        }

        .pagination-ellipsis {
            padding: 8px 12px;
            color: #666;
        }

        /* รูปแบบของข้อมูลผลลัพธ์ */
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
            transition: border-color 0.3s ease;
        }

        .results-per-page:focus {
            border-color: #007bff;
            outline: none;
        }

        /* การออกแบบแบบตอบสนองสำหรับหน้าจอขนาดเล็ก */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 100%;
                margin: 1rem;
                padding: 15px;
            }

            .container h2 {
                font-size: 18px;
            }

            .search-filter {
                flex-direction: column;
                gap: 10px;
            }

            .container input,
            .container select,
            .container button {
                width: 100%;
                font-size: 12px;
            }

            .container table {
                font-size: 12px;
                /* ลดขนาดตัวอักษรในหน้าจอเล็ก */
            }

            .container th,
            .container td {
                padding: 8px;
                /* ลด padding ในหน้าจอเล็ก */
            }

            .pagination a,
            .pagination span {
                padding: 6px 10px;
                font-size: 12px;
            }

            .results-info {
                font-size: 12px;
            }
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
        <h2>Admin Chat Management</h2>
        <div class="search-filter">
            <input type="text" id="searchInput" placeholder="🔍 Search by user..." onkeyup="filterTable()">
            <select id="sortOrder" onchange="sortTable()">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
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
        <div class="pagination">
            <?php
            // Prev button (always visible)
            if ($page > 1) {
                echo "<a href='?page=" . ($page - 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>«</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>«</a>";
            }

            // Display page numbers (1 2 3 ... 10)
            $max_pages_to_show = 5; // Number of pages to show (e.g., 1 2 3 ... 10)
            $half_pages = floor($max_pages_to_show / 2);

            $start_page = max(1, $page - $half_pages);
            $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

            if ($end_page - $start_page + 1 < $max_pages_to_show) {
                $start_page = max(1, $end_page - $max_pages_to_show + 1);
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo "<a href='?page=$i&results_per_page=$results_per_page' class='pagination-number $active'>$i</a>";
            }

            if ($end_page < $total_pages) {
                echo "<span class='pagination-ellipsis'>...</span>";
                echo "<a href='?page=$total_pages&results_per_page=$results_per_page' class='pagination-number'>$total_pages</a>";
            }

            // Next button (always visible)
            if ($page < $total_pages) {
                echo "<a href='?page=" . ($page + 1) . "&results_per_page=$results_per_page' class='pagination-arrow'>»</a>";
            } else {
                echo "<a href='#' class='pagination-arrow disabled'>»</a>";
            }
            ?>
        </div>

        <!-- Display results -->
        <div class="results-info">
            Results: <?php echo $start_result . " - " . $end_result . " of " . $total_records . " messages"; ?>
            <select class="results-per-page" onchange="changeResultsPerPage(this.value)">
                <option value="10" <?php echo $results_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $results_per_page == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $results_per_page == 50 ? 'selected' : ''; ?>>50</option>
                <!-- Option for all messages -->
                <option value="<?php echo $total_records; ?>" <?php echo $results_per_page == $total_records ? 'selected' : ''; ?>>All</option>
            </select>
        </div>
    </div>

    <script>
        function changeResultsPerPage(perPage) {
            // If "All" is selected, use the total number of records, otherwise use the selected value
            const finalPerPage = perPage === "<?php echo $total_records; ?>" ? "<?php echo $total_records; ?>" : perPage;
            window.location.href = `?page=1&results_per_page=${finalPerPage}`;
        }

        // Store original rows for filtering
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

            // Show according to selected results per page
            const resultsPerPage = <?php echo $results_per_page; ?>;
            const pageRows = filteredRows.slice(0, resultsPerPage);
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