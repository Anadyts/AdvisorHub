<?php
session_start();
include('../components/navbar.php');
require('../server.php');

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

// คำสั่ง SQL ดึงข้อมูลอาจารย์ที่ปรึกษา
$sql_advisors = "
    SELECT 
        a.advisor_id, 
        CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS name
    FROM advisor a
    ORDER BY a.advisor_id
";

$result_advisors = $conn->query($sql_advisors);

// ตรวจสอบว่ามีการเลือกอาจารย์หรือไม่
$selected_advisor_id = isset($_GET['advisor_id']) ? $conn->real_escape_string($_GET['advisor_id']) : null;
$selected_advisor_name = '';
$students_result = null;

if ($selected_advisor_id) {
    // ดึงชื่ออาจารย์ที่เลือก
    $sql_advisor_name = "
        SELECT CONCAT(advisor_first_name, ' ', advisor_last_name) AS name
        FROM advisor
        WHERE advisor_id = '$selected_advisor_id'
    ";
    $advisor_result = $conn->query($sql_advisor_name);
    $selected_advisor_name = $advisor_result->fetch_assoc()['name'] ?? 'ไม่พบอาจารย์';

    // คำสั่ง SQL ดึงข้อมูลนิสิตที่อาจารย์รับเป็นที่ปรึกษา
    $sql_students = "
        SELECT 
            CONCAT(s.student_first_name, ' ', s.student_last_name) AS student_name
        FROM student s
        INNER JOIN advisor_request ar 
            ON JSON_CONTAINS(ar.student_id, CONCAT('\"', s.student_id, '\"'))
        WHERE 
            ar.advisor_id = '$selected_advisor_id'
            AND ar.is_advisor_approved = 1 
            AND ar.is_admin_approved = 1 
            AND ar.partner_accepted = 1 
            AND (ar.is_even = 0 OR ar.is_even = 1)
        GROUP BY s.student_id, s.student_first_name, s.student_last_name
        ORDER BY s.student_id
    ";
    $students_result = $conn->query($sql_students);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Logo.png">
    <title>รายชื่อนิสิตที่อาจารย์รับเป็นที่ปรึกษา</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', 'Segoe UI', Arial, sans-serif;
            background-color: rgb(255, 255, 255);
            text-align: center;
            line-height: 1.6;
        }

        table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 3rem;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: center;
        }

        th {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        td {
            color: #4b5563;
            transition: all 0.2s ease;
        }

        .topic {
            color: #1f2937;
            margin: 20px 0;
            font-weight: 700;
            font-size: 1.8rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        tr:hover {
            background-color: #f8fafc;
            transition: background-color 0.3s ease;
        }

        a {
            color: #4b5563;
            text-decoration: none;
        }

        a:hover {
            color: #ea580c;
            text-decoration: underline;
        }

        table {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <?php 
        if(empty($_SESSION['account_id'])){
            renderNavbar(['home', 'login','advisor', 'statistics', "Dashboard"]);
        } elseif(isset($_SESSION['account_id']) && ($_SESSION['role'] == 'student' || $_SESSION['role'] == 'advisor')){
            renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']);
        } elseif(isset($_SESSION['account_id']) && $_SESSION['role'] == 'admin'){
            renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
        }
    ?>



    <?php if ($selected_advisor_id && $selected_advisor_name): ?>
        <h2 class="topic">รายชื่อนิสิตที่อาจารย์ <?php echo htmlspecialchars($selected_advisor_name); ?> รับเป็นที่ปรึกษา</h2>
        <table>
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุลนิสิต</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $student_index = 1;
                if ($students_result && $students_result->num_rows > 0) {
                    while ($row = $students_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$student_index}</td>
                                <td style='text-align: left;'>{$row['student_name']}</td>
                              </tr>";
                        $student_index++;
                    }
                } else {
                    echo "<tr><td colspan='2'>ไม่มีนิสิตที่อาจารย์รับเป็นที่ปรึกษา</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>

<?php
$conn->close();
?>