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

// คำสั่งคิวรี่เพื่อดึงคู่แชทที่ไม่ซ้ำกันพร้อมเวลาล่าสุด
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
";

$result = mysqli_query($conn, $sql);
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
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(255, 255, 255);
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: auto;
            margin-top: 2rem;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgb(136, 134, 134);
        }

        .container h2 {
            text-align: center;
            color: #333;
        }

        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .container input,
        select,
        button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .container button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .container button:hover {
            background: #0056b3;
        }

        .container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .container th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .container th {
            background: #007bff;
            color: white;
        }

        .container tr:nth-child(even) {
            background: #f9f9f9;
        }

        .container a {
            text-decoration: none;
            color: #007bff;
        }

        .container a:hover {
            text-decoration: underline;
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
                    <th></th>
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
    </div>

    <script>
        // เก็บแถวต้นฉบับไว้สำหรับการรีเซ็ตหลังจาก filter แล้ว
        const tbody = document.getElementById('chatTable');
        const originalRows = Array.from(tbody.getElementsByTagName('tr'));

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
            filteredRows.forEach(row => tbody.appendChild(row));

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

            // สร้างฟอร์มแบบไดนามิกเพื่อส่งคู่ที่เลือกไปยังสคริปต์ส่งออก
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

        window.onload = sortTable;
    </script>
</body>

</html>