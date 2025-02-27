<?php
include('../components/navbar.php');
include('../server.php');

session_start();

// ถ้าไม่ได้บันทึก session id ของอาจารย์ ให้กลับไปหน้า advisor
if (empty($_SESSION['advisor_id'])) {
  header('location: /AdvisorHub/advisor');
}

if (isset($_POST['logout'])) {
  session_destroy();
  header('location: /AdvisorHub/login');
}

if (isset($_POST['profile'])) {
  header('location: /AdvisorHub/profile');
}

//ไม่ให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
  header('location: /AdvisorHub/advisor');
}

// ถ้าไม่ได้ล็อกอิน ให้กลับไปหน้า login
if (!isset($_SESSION['username']) && !isset($_SESSION['account_id'])) {
  die(header("location:http://localhost/AdvisorHub/login"));
}

$id = $_SESSION['account_id'];
$username = $_SESSION['username'];

// sql สำหรับเช็คว่าอยู่ใน role อะไร (advisor, student, admin)
$check_sql = "SELECT role FROM account WHERE account_id = '{$id}'";
$check_result = mysqli_query($conn, $check_sql);
$check_row = mysqli_fetch_array($check_result);

// condition สำหรับ sql ดึงข้อมูลนิสิต
if ($check_row['role'] == 'student') {
  $sql = "SELECT * FROM student WHERE student_id = '{$id}'";
  $result = mysqli_query($conn, $sql);
  $row = mysqli_fetch_array($result);
} else {
  header('location: /AdvisorHub/advisor');
}

// sql สำหรับเรียกข้อมูลอาจารย์
$advisor_sql = "SELECT advisor_first_name, advisor_last_name FROM advisor WHERE advisor_id = '{$_SESSION["advisor_id"]}'";
$advisor_result = mysqli_query($conn, $advisor_sql);
$advisor_row = mysqli_fetch_array($advisor_result);

?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" href="../Logo.png">
</head>

<body>
  <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

  <div class="container my-5">
    <h1 class="head-thesis">Thesis Request Form</h1>
    <form action="advisor_request.php" method="POST">
      <!-- academic year and semester -->
      <div class="mb-3 row align-items-center">

        <div class="col-auto">
          <input type="hidden" class="form-control" id="academic_year" name="academic_year"
            value="<?php echo date("Y") + 542 ?>" style="width: 100px;">
        </div>

        <div class="col-auto">
          <input type="hidden" id="semester" name="semester" value="2" class="form-control">
        </div>
      </div>

            <!-- อาจารย์ที่ปรึกษา -->
      <div class="mb-3">
        <h3 for="advisorName">Advisor: <?php echo $advisor_row['advisor_first_name']. ' ' . $advisor_row['advisor_last_name'];  ?></h3>
        <input type="hidden" class="form-control" id="advisorName" name="advisorName"
          value="<?php echo $advisor_row['advisor_first_name'] . ' ' . $advisor_row['advisor_last_name']; ?>" readonly>
      </div>

      <!-- Dropdown -->
      <div class="mb-3 d-flex align-items-center">
        <label for="thesisType" class="me-2">ทำวิทยานิพนธ์ประเภท: </label>
        <select id="thesisType" name="thesisType" class="form-select w-auto" onchange="toggleFields()">
          <option value="single" selected>เดี่ยว</option>
          <option value="pair">คู่</option>
        </select>
      </div>

      <!-- ฟิลด์ทำเดี่ยว -->
      <div id="singleFields">
        <h5>ข้อมูลนิสิต</h5>

        <div class="row align-items-center mb-3">
          <div class="col-md-6">
            <input type="hidden" class="form-control" id="singleStudentID" name="singleStudentID"
              value="<?php echo $row['student_id']; ?>" readonly>
          </div>
        </div>
      </div>

      <!-- ฟิลด์ทำคู่ -->
      <div id="pairFields" class="hidden">
        <h5>ข้อมูลนิสิต</h5>
        <div class="row align-items-center mb-3">
          <!-- input รหัสนิสิต -->
          <div class="col-md-6">
            <input type="hidden" class="form-control" id="pairStudentID1" name="pairStudentID1"
              value="<?php echo $row['student_id']; ?>" readonly>
              <label for="pairStudentID2" class="form-label mt-4">รหัสนิสิตของคู่</label>
              <input type="text" class="form-control" id="pairStudentID2" name="pairStudentID2" placeholder="กรุณากรอกรหัสนิสิตของคู่" onblur="fetchStudentData()">
          </div>
        </div>
      </div>

      <!-- ชื่อเรื่องไทย -->
      <div class="mb-3">
        <label for="thesisTitleThai" class="form-label">ชื่อเรื่อง (ภาษาไทย):</label>
        <input type="text" class="form-control" id="thesisTitleThai" name="thesisTitleThai" required>
      </div>

      <!-- ชื่อเรื่อง eng -->
      <div class="mb-3">
        <label for="thesisTitleEnglish" class="form-label">ชื่อเรื่อง (ภาษาอังกฤษ):</label>
        <input type="text" class="form-control" id="thesisTitleEnglish" name="thesisTitleEnglish" required>
      </div>

      <!-- รายละเอียดสังเขป -->
      <div class="mb-3">
        <label for="thesisDescription" class="form-label">รายละเอียดวิทยานิพนธ์โดยสังเขป:</label>
        <textarea class="form-control" id="thesisDescription" name="thesisDescription" rows="4" required></textarea>
      </div>

      <!-- input เปล่า ไว้ส่ง id ของ อจ. -->
      <input type="text" hidden name="advisor_id" id="advisor_id" value="<?php echo $_SESSION['advisor_id'] ?>">

      <!-- submit -->
      <div class="text-start mt-4">
        <button type="submit" class="btn" style="color:white; background-color: #ff9300;">ส่งคำร้อง</button>
      </div>

    </form>
  </div>

  <script>
    function toggleFields() {
      const thesisType = document.getElementById('thesisType').value;
      const singleFields = document.getElementById('singleFields');
      const pairFields = document.getElementById('pairFields');

      // ถ้าเลือกทำเดี่ยว ให้แสดงฟิลด์สำหรับ ทำเดี่ยว และซ่อนฟิลด์สำหรับ ทำคู่
      if (thesisType === 'single') {
        singleFields.style.display = 'block';
        pairFields.style.display = 'none';
      }
      // ถ้าเลือกทำคู่ ให้แสดงฟิลด์สำหรับ ทำคู่ และซ่อนฟิลด์สำหรับ ทำเดี่ยว
      else if (thesisType === 'pair') {
        singleFields.style.display = 'none';
        pairFields.style.display = 'block';
      }
    }

    // ตั้งค่าเริ่มต้นให้แสดงฟิลด์สำหรับ ทำเดี่ยว
    window.onload = toggleFields;
  </script>
</body>

</html>