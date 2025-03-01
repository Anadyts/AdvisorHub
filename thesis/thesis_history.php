<?php
session_start();
require('../server.php');
include('../components/navbar.php');
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

//ไม่ให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

if (isset($_POST['chat'])) {
    $_SESSION['receiver_id'] = $_POST['chat'];
    header('location: /AdvisorHub/topic_chat/topic_chat.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Information</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../Logo.png">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'>
    <!-- jQuery, Moment.js, และ Daterangepicker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
</head>

<body>

    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']) ?>

    <?php
    if (isset($_SESSION['advisor_id'])) {
        // Get advisor information from session
        $advisor_id = $_SESSION['advisor_id'];
        $sql = "SELECT * FROM advisor_profile WHERE advisor_id = '$advisor_id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        $advisor_id = $row['advisor_id'];
        $expertise = json_decode($row['expertise']);
        $advisor_interests = $row['advisor_interests'];
        $img = $row['img'];

        // Get advisor information from advisor table
        $sql = "SELECT * FROM advisor WHERE advisor_id = '$advisor_id'";
        $result_advisor = $conn->query($sql);
        $row_advisor = $result_advisor->fetch_assoc();

        $advisor_first_name = $row_advisor['advisor_first_name'];
        $advisor_last_name = $row_advisor['advisor_last_name'];
        $advisor_tel = $row_advisor['advisor_tel'];
        $advisor_email = $row_advisor['advisor_email'];

        // Get thesis information from thesis table
        $sql = "SELECT * FROM thesis WHERE advisor_id = '$advisor_id' ORDER BY issue_date DESC";
        $result_thesis = $conn->query($sql);
        $thesis_count = $result_thesis->num_rows;

        if ($result_thesis->num_rows > 0) {
            $row_thesis = $result_thesis->fetch_assoc();

            $thesis_title = $row_thesis['thesis_title'];
            $authors = $row_thesis['authors'];
            $keywords = $row_thesis['keywords'];
            $issue_date = $row_thesis['issue_date'];
            $publisher = $row_thesis['publisher'];
            $abstract = $row_thesis['abstract'];
            $thesis_file = $row_thesis['thesis_file'];
            $thesis_file_type = $row_thesis['thesis_file_type'];
        }

        // Get student count from advisor_request table
        $sql = "SELECT 
        COUNT(*) + SUM(CASE WHEN is_even = 1 THEN 1 ELSE 0 END) AS student_count
        FROM advisor_request
        WHERE advisor_id = '$advisor_id'
        AND is_advisor_approved = 1
        AND is_admin_approved = 1";
        $result_student = $conn->query($sql);
        $row_student = $result_student->fetch_assoc();
        $student_count = $row_student['student_count'];
    } else {
        echo "ไม่พบข้อมูล";
    }
    ?>

    <div class="container">
        <div class="row">
            <div class="col-xl">

                <!-- Start Profile -->
                <div class="card">
                    <!--img-->
                    <div class="rounded-top-3 lazy-load" data-bg="https://english.nu.ac.th/wp-content/uploads/slider/cache/811ae9168f083da2d84d70eb287510f1/Nu-view01.jpg"
                        style="background-position: center; background-size: cover; background-repeat: no-repeat; height: 300px;">
                    </div>
                    <div class="card-body p-md-5">
                        <div class="d-flex flex-column gap-5">
                            <!--img-->
                            <div class="position-relative">
                                <img src="<?= $img ?>" class="avatar-circle" alt="">
                            </div>
                            <div class="d-flex flex-column gap-5">
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-md-row flex-column justify-content-between gap-2">
                                        <!--heading-->
                                        <div>
                                            <h2 class="mt-3 fw-bold"> <?php echo $advisor_first_name . " " . $advisor_last_name; ?> </h2>
                                            <!--content-->
                                            <div class="d-flex flex-lg-row flex-column gap-2">
                                                <small class="fw-medium text-gray-800">A teacher at Naresuan University</small>
                                                <small class="fw-medium" style="color: #fe7801">Computer Science and Information Technology (CSIT).</small>
                                            </div>
                                        </div>
                                        <!--button-->
                                        <div class="d-flex flex-row gap-3 align-items-center">
                                            <form action="" method="post">
                                                <button name="chat" value="<?= $advisor_id ?>" class="btn btn-outline-info">
                                                    <i class='bx bxs-message-dots fs-6'></i>
                                                    Ask questions
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-md-row flex-column gap-md-4 gap-2">
                                        <div class="d-flex flex-row gap-2 align-items-center lh-1">
                                            <!--icon-->
                                            <span>
                                                <i class="fa-solid fa-envelope"></i>
                                            </span>
                                            <span>
                                                <!--text-->
                                                <span class="text-gray-800"> <?php echo $advisor_email ?> </span>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-row gap-2 align-items-center lh-1">
                                            <!--icon-->
                                            <span>
                                                <i class="fa-solid fa-phone"></i>
                                            </span>
                                            <!--text-->
                                            <span>
                                                <span class="text-gray-800"> <?php echo $advisor_tel ?> </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <!--heading-->
                                    <h3 class="mb-0">Expertise</h3>

                                    <p class="mb-1 fs-6">
                                        <?php
                                        foreach ($expertise as $exp) {
                                            echo '<span class="badge rounded-pill text-bg-secondary">' . htmlspecialchars($exp) . '</span> ';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Profile -->

                <!-- Start Thesis -->
                <div class="card">
                    <div class="tab-content p-4">
                        <div class="tab-pane active show" id="projects-tab" role="tabpanel">

                            <div class="d-flex align-items-center flex-wrap mb-4">
                                <h4 class="card-title mb-0 me-2">Thesis</h4>
                                <span id="thesisCount" class="badge rounded-pill bg-secondary opacity-75 text-white px-2 py-1">
                                    0
                                </span>

                                <div class="d-flex justify-content-end align-items-center gap-2 ms-auto">
                                    <div class="btn-group">
                                        <button id="listViewBtn" class="btn btn-outline-primary btn-sm active">
                                            <i class="bi bi-list"></i>
                                        </button>
                                        <button id="gridViewBtn" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-grid"></i>
                                        </button>
                                    </div>

                                    <div id="reportrange" style="background: #fff; cursor: pointer; white-space: nowrap;" class="form-control">
                                        <i class="fa fa-calendar"></i>&nbsp;
                                        <span></span>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="position-relative search-box">
                                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                            <input type="text" id="live_search" class="form-control custom-search-input ps-5" placeholder="Search Thesis or Keywords...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2 thesis-list" id="thesis-container">
                                <!-- Thesis items will be displayed here (fetch_thesis.php) -->
                            </div>

                        </div><!-- end tab pane -->
                    </div>
                </div><!-- end card -->
                <!-- End Thesis -->

            </div><!-- end col -->
        </div><!-- end col -->
    </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- Ajax -->
    <script type="text/javascript">
        $(document).ready(function() {
            let start = moment('2000-01-01');
            let end = moment();
            let viewMode = 'list';
            let searchQuery = "";

            function fetchThesis(startDate, endDate, searchQuery, viewMode) {
                $.ajax({
                    url: 'filter_thesis.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        search_query: searchQuery,
                        view_mode: viewMode
                    },
                    success: function(response) {
                        $('#thesis-container').html(response.html);
                        $('#thesisCount').text(response.thesis_count); // อัปเดตจำนวนวิทยานิพนธ์
                    }
                });
            }

            function cb(start, end) {
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            }

            // Initialize Date Range Picker
            $('#reportrange').daterangepicker({
                startDate: start,
                endDate: end,
                opens: 'right',
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'All Time': [moment('2000-01-01'), moment()]
                }
            }, function(newStart, newEnd) {
                start = newStart;
                end = newEnd;
                cb(start, end); // อัปเดต UI
                fetchThesis(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), searchQuery, viewMode);
            });

            cb(start, end); // แสดงค่าเริ่มต้นที่ UI

            // Live Search
            $('#live_search').on('input', function() {
                searchQuery = $(this).val();
                fetchThesis(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), searchQuery, viewMode);
            });

            // Toggle View Mode
            $("#listViewBtn").click(function() {
                viewMode = 'list';
                fetchThesis(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), searchQuery, viewMode);
            });

            $("#gridViewBtn").click(function() {
                viewMode = 'grid';
                fetchThesis(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), searchQuery, viewMode);
            });

            // โหลดข้อมูลตอนเริ่มต้น
            fetchThesis(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), searchQuery, viewMode);
        });
    </script>

    <!-- Switch view mode -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const listViewBtn = document.getElementById("listViewBtn");
            const gridViewBtn = document.getElementById("gridViewBtn");
            const thesisContainer = document.getElementById("thesis-container");

            if (listViewBtn && gridViewBtn && thesisContainer) {
                listViewBtn.addEventListener("click", function() {
                    thesisContainer.classList.remove("thesis-grid");
                    thesisContainer.classList.add("thesis-list");

                    listViewBtn.classList.add("active");
                    gridViewBtn.classList.remove("active");
                });

                gridViewBtn.addEventListener("click", function() {
                    thesisContainer.classList.remove("thesis-list");
                    thesisContainer.classList.add("thesis-grid");

                    gridViewBtn.classList.add("active");
                    listViewBtn.classList.remove("active");
                });
            }
        });
    </script>

    <!-- Loading bg img -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let lazyDivs = document.querySelectorAll(".lazy-load");
            lazyDivs.forEach(div => {
                let imgSrc = div.getAttribute("data-bg");
                let img = new Image();
                img.src = imgSrc;
                img.onload = () => {
                    div.style.backgroundImage = `url(${imgSrc})`;
                };
            });
        });
    </script>
</body>

</html>