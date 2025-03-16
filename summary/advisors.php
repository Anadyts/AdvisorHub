<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$servername = "localhost";
$username = "root";  // ค่าเริ่มต้นของ XAMPP
$password = "";      // ค่าเริ่มต้นของ XAMPP
$dbname = "thesis_hub";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// SQL ดึงข้อมูลอาจารย์ที่ปรึกษา
$sql = "
    SELECT 
        a.advisor_id, 
        CONCAT(a.advisor_first_name, ' ', a.advisor_last_name) AS name,
        COUNT(ar.student_id) AS students,
        SUM(CASE WHEN ar.is_even = 0 THEN 1 ELSE 0 END) AS single,
        SUM(CASE WHEN ar.is_even = 1 THEN 1 ELSE 0 END) AS pair,
        SUM(CASE WHEN ar.is_even = 0 THEN 1 ELSE 0 END) + SUM(CASE WHEN ar.is_even = 1 THEN 1 ELSE 0 END) AS total
    FROM advisor a
    LEFT JOIN advisor_request ar ON a.advisor_id = ar.advisor_id
    GROUP BY a.advisor_id
";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// ปิดการเชื่อมต่อ
$conn->close();

// ส่งข้อมูลกลับเป็น JSON
echo json_encode($data);
?>
