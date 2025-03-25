<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
    exit();
}

if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
    exit();
}

if(isset($_SESSION['username']) && $_SESSION['role'] != 'admin'){
    header('location: /AdvisorHub/home');
}
if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="../Logo.png">
    <link rel="stylesheet" href="style/index.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script/chart_student_alltime.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    
</head>
<body>
    <?php renderNavbar(allowedPages: ['home', 'advisor','statistics']) ?>
    <div class="container">
        <!-- Partner Card -->
        <a href="/AdvisorHub/dashboard/partner_pending.php">
            <div class="card">
                <div class="card-header partner">คำขอที่รอ Partner อนุมัติ</div>
                <div class="card-body">
                    <div class="card-number">
                        <?php
                            $sql = "SELECT COUNT(*) as count FROM advisor_request WHERE partner_accepted = 0 AND is_admin_approved != 2 AND is_advisor_approved != 2";
                            $result = $conn->query($sql);
                            echo $result->fetch_assoc()['count'];
                        ?>
                    </div>
                    <div class="card-label">คำขอที่รอดำเนินการ</div>
                </div>
                <div class="card-footer">อัปเดตล่าสุด: <?php echo date("Y-m-d H:i:s"); ?></div>
            </div>
        </a>
        <!-- Admin Card -->
        <a href="/AdvisorHub/dashboard/admin_pending.php">
            <div class="card">
                <div class="card-header admin">คำขอที่รอ Admin อนุมัติ</div>
                <div class="card-body">
                    <div class="card-number">
                        <?php
                            $sql = "SELECT COUNT(*) as count FROM advisor_request WHERE partner_accepted != 2 AND is_admin_approved = 0 AND is_advisor_approved != 2";
                            $result = $conn->query($sql);
                            echo $result->fetch_assoc()['count'];
                        ?>
                    </div>
                    <div class="card-label">คำขอที่รอดำเนินการ</div>
                </div>
                <div class="card-footer">อัปเดตล่าสุด: <?php echo date("Y-m-d H:i:s"); ?></div>
            </div>
        </a>

        <!-- Advisor Card -->
        <a href="/AdvisorHub/dashboard/advisor_pending.php">
            <div class="card">
                <div class="card-header advisor">คำขอที่รอ Advisor อนุมัติ</div>
                <div class="card-body">
                    <div class="card-number">
                    <?php
                        $sql = "SELECT COUNT(*) as count FROM advisor_request WHERE partner_accepted != 2 AND is_admin_approved != 2 AND is_advisor_approved = 0";
                        $result = $conn->query($sql);
                        echo $result->fetch_assoc()['count'];
                    ?>
                    </div>
                    <div class="card-label">คำขอที่รอดำเนินการ</div>
                </div>
                <div class="card-footer">อัปเดตล่าสุด: <?php echo date("Y-m-d H:i:s"); ?></div>
            </div>
        </a>


        <a href="/AdvisorHub/dashboard/accepted_request.php">
            <div class="card">
                <div class="card-header accepted">คำขอที่ถูกอนุมัติ</div>
                <div class="card-body">
                    <div class="card-number">
                    <?php
                        $sql = "SELECT COUNT(*) as count FROM advisor_request WHERE partner_accepted = 1 AND is_admin_approved = 1 AND is_advisor_approved = 1";
                        $result = $conn->query($sql);
                        echo $result->fetch_assoc()['count'];
                    ?>
                    </div>
                    <div class="card-label">คำขอที่ถูกอนุมัติ</div>
                </div>
                <div class="card-footer">อัปเดตล่าสุด: <?php echo date("Y-m-d H:i:s"); ?></div>
            </div>
        </a>


        <a href="/AdvisorHub/dashboard/rejected_request.php">
            <div class="card">
                <div class="card-header rejected">คำขอที่ถูกปฎิเสธ</div>
                <div class="card-body">
                    <div class="card-number">
                    <?php
                        $sql = "SELECT COUNT(*) as count FROM advisor_request WHERE partner_accepted = 2 OR is_admin_approved = 2 OR is_advisor_approved = 2";
                        $result = $conn->query($sql);
                        echo $result->fetch_assoc()['count'];
                    ?>
                    </div>
                    <div class="card-label">คำขอที่ถูกปฎิเสธ</div>
                </div>
                <div class="card-footer">อัปเดตล่าสุด: <?php echo date("Y-m-d H:i:s"); ?></div>
            </div>
        </a>
        
        <!--------------------------- กราฟแสดงจำนวนนิสิตทั้งหมดของอาจารย์แต่ละคน ------------------------->
        <?php
        $sql = "SELECT 
                advisor.advisor_id, 
                CONCAT(advisor.advisor_first_name, ' ', advisor.advisor_last_name) AS advisor_name, 
                SUM(
                    CASE 
                        WHEN advisor_request.is_advisor_approved = 1 
                            AND advisor_request.is_admin_approved = 1 
                            AND advisor_request.is_even = 0 THEN 1
                        WHEN advisor_request.is_advisor_approved = 1 
                            AND advisor_request.is_admin_approved = 1 
                            AND advisor_request.is_even = 1 THEN 2
                        ELSE 0
                    END
                ) AS total_students
            FROM advisor_request
            JOIN advisor ON advisor_request.advisor_id = advisor.advisor_id
            GROUP BY advisor_request.advisor_id
            ORDER BY total_students DESC";

        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        ?>

        <div class="container-fluid" id="chartContainer" data-chart='<?php echo json_encode($data); ?>'>
            <h4>กราฟแสดงจำนวนนิสิตทั้งหมดของอาจารย์แต่ละท่าน</h4>
            <canvas id="advisorChart"></canvas>
        </div>
        <!-- ************************************************************************************************ -->

    </div>
</body>
</html>