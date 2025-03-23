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
    </div>
</body>
</html>