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
$sql = "
    SELECT 
        a.advisor_id, 
        CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS name,
        IFNULL(COUNT(ar.student_id), 0) + IFNULL(SUM(CASE WHEN ar.is_even = 1 THEN 1 ELSE 0 END), 0) AS students, 
        IFNULL(SUM(CASE WHEN ar.is_even = 0 THEN 1 ELSE 0 END), 0) AS single,
        IFNULL(SUM(CASE WHEN ar.is_even = 1 THEN 1 ELSE 0 END), 0) AS pair,
        IFNULL(SUM(CASE WHEN ar.is_even = 1 THEN 1 ELSE 0 END), 0) + IFNULL(SUM(CASE WHEN ar.is_even = 0 THEN 1 ELSE 0 END), 0) AS total
    FROM advisor a
    LEFT JOIN advisor_request ar 
        ON a.advisor_id = ar.advisor_id 
        AND ar.is_advisor_approved = 1 
        AND ar.is_admin_approved = 1 
        AND ar.time_stamp BETWEEN STR_TO_DATE(CONCAT(YEAR(CURDATE()) - 1, '-11-01'), '%Y-%m-%d') 
        AND STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-10-31'), '%Y-%m-%d')
    GROUP BY a.advisor_id
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Logo.png">
    <title>รายชื่ออาจารย์ที่ปรึกษา</title>
    <link rel="icon" href="../Logo.png">
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

        .highlight {
            background: rgb(255, 255, 255);
            font-weight: bold;
            color: #dc2626;
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

        tfoot td {
            background: #f1f5f9;
            /* สีพื้นหลังเดียวกันทั้งแถว */
            font-weight: bold;
            color: #1f2937;
        }

        /* ป้องกัน .highlight มีผลใน tfoot */
        tfoot .highlight {
            background: #f1f5f9;
            /* คงสีพื้นหลังให้เหมือนกัน */
            color: #dc2626;
            /* คงสีตัวอักษรแดง */
        }

        /* Animation for table load */
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
    <!-- เพิ่มฟอนต์ Prompt จาก Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>

    <?php 
        if(empty($_SESSION['account_id'])){
            renderNavbar(['home', 'login','advisor', 'statistics', "Dashboard"]);
        } elseif(isset($_SESSION['account_id']) && $_SESSION['role'] == 'student' || $_SESSION['role'] == 'advisor'){
            renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']);
        } elseif(isset($_SESSION['account_id']) && $_SESSION['role'] == 'admin'){
            renderNavbar(allowedPages: ['home', 'advisor', 'statistics']);
        }
    ?>
    <h2 class="topic">รายชื่ออาจารย์ที่ปรึกษา และจำนวนที่รับเป็นอาจารย์ที่ปรึกษา</h2>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รายชื่ออาจารย์ที่ปรึกษา</th>
                <th>จำนวนนิสิตที่รับเป็นที่ปรึกษา(คน)</th>
                <th>นิสิตทำแบบเดี่ยว (เรื่อง)</th>
                <th>นิสิตทำแบบคู่ (เรื่อง)</th>
                <th>จำนวนหัวข้อวิทยานิพนธ์ (เรื่อง)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $index = 1;
            $total_students = 0;
            $total_single = 0;
            $total_pair = 0;
            $total_thesis = 0;

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $students = $row["students"] ?? 0;
                    $single = $row["single"] ?? 0;
                    $pair = $row["pair"] ?? 0;
                    $total = $row["total"] ?? 0;

                    $total_students += intval($row["students"]);
                    $total_single += $single;
                    $total_pair += $pair;
                    $total_thesis += $total;
                    echo "<tr>
                            <td>{$index}</td>
                            <td style='text-align: left;'>{$row["name"]}</td>
                            <td class='highlight'>{$row["students"]}</td>
                            <td>{$row["single"]}</td>
                            <td>{$row["pair"]}</td>
                            <td>{$row["total"]}</td>
                          </tr>";
                    $index++;
                }
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>รวมทั้งหมด</strong></td>
                <td class='highlight'><strong><?php echo $total_students; ?></strong></td>
                <td><strong><?php echo $total_single; ?></strong></td>
                <td><strong><?php echo $total_pair; ?></strong></td>
                <td><strong><?php echo $total_thesis; ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <h2 class="topic">รายชื่อนิสิตที่ยังไม่มีอาจารย์ที่ปรึกษา</h2>
    <?php
    // Add this SQL query after your existing query and before the closing </body> tag
    $sql_students = "SELECT 
        s.student_id,
        s.student_first_name,
        s.student_last_name,
        s.student_tel,
        s.student_email,
        s.student_department
    FROM student s
    LEFT JOIN advisor_request ar 
        ON JSON_CONTAINS(ar.student_id, CONCAT('\"', s.student_id, '\"'))
    WHERE (
        ar.advisor_request_id IS NULL
        OR (
            ar.student_id IS NOT NULL
            AND ar.is_advisor_approved = 0
            AND ar.is_admin_approved = 0
        )
    )
    AND NOT EXISTS (
        SELECT 1 
        FROM advisor_request ar2 
        WHERE JSON_CONTAINS(ar2.student_id, CONCAT('\"', s.student_id, '\"'))
        AND ar2.is_advisor_approved = 1 
        AND ar2.is_admin_approved = 1
    )
    GROUP BY 
        s.student_first_name,
        s.student_last_name,
        s.student_tel,
        s.student_email,
        s.student_department
    ";

    $result_students = $conn->query($sql_students);
    ?>

    <!-- Add this table after your existing h2 for students without advisors -->
    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รหัสนิสิต</th>
                <th>ชื่อ-นามสกุล</th>
                <?php 
                    if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
                        echo "<th>เบอร์โทรศัพท์</th>";
                }?>
                
                <th>อีเมล</th>
                <th>สาขา</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $student_index = 1;
            if ($result_students->num_rows > 0) {
                while ($row = $result_students->fetch_assoc()) {
                    echo "<tr>
                        <td>{$student_index}</td>
                        <td>{$row['student_id']}</td>
                        <td style='text-align: left;'>{$row['student_first_name']} {$row['student_last_name']}</td>
                        ";
                    
                        if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
                            echo "<td>{$row['student_tel']}</td>";
                        }
                    
                    echo"
                        
                        <td>{$row['student_email']}</td>
                        <td>{$row['student_department']}</td>
                    </tr>";
                    $student_index++;
                }
            } else {
                echo "<tr><td colspan='5'>ไม่มีนิสิตที่ยังไม่มีอาจารย์ที่ปรึกษา</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>

</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>