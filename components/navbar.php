<?php
function renderNavbar($allowedPages)
{

?>
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../Logo.png">
    <nav>
        <div class="logo">
            <img src="../CSIT.png" alt="" width="250px">
        </div>
        <ul>
            <?php
            // รายการเมนูทั้งหมด
            $menuItems = [
                "home" => "/AdvisorHub/home",
                "advisor" => "/AdvisorHub/advisor",
                "inbox" => "/AdvisorHub/inbox",
                "thesis" => "/AdvisorHub/thesis/thesis.php",
                "statistics" => "/AdvisorHub/statistics",
                "Teams" => "/AdvisorHub/thesis_resource_list/thesis_resource_list.php",
                "login" => "/AdvisorHub/login"
            ];

            // วนลูปแสดงเฉพาะเมนูที่อยู่ใน $allowedPages
            foreach ($allowedPages as $key) {
                if (isset($menuItems[$key])) {
                    echo "<li><a href='{$menuItems[$key]}'>" . ucfirst($key) . "</a></li>";
                }
            }

            if (isset($_SESSION['username']) && ($_SESSION['role'] == 'advisor' ||  $_SESSION['role'] == 'student')) {
                echo "<li><a href='/AdvisorHub/advisor_approved/request.php'>Request</a></li>";
             
            }elseif(isset($_SESSION['username']) && $_SESSION['role'] == 'admin'){
                echo "<li><a href='/AdvisorHub/chat_activity'>Activity</a></li>";
                echo "<li><a href='/AdvisorHub/dashboard/'>Dashboard</a></li>";
                echo "<li><a href='/AdvisorHub/admin_approved/'>Request</a></li>";
            }
            
            ?>


        </ul>

        <div class="userProfile">
            <?php
            if (isset($_SESSION['username'])) {
                echo '<h2>' . $_SESSION['username'] . '</h2>';
                echo "<i class='bx bxs-user-circle'></i>";
                echo "<div class='dropdown'>
                        <form action='' method='post'>
                    ";
                
                if($_SESSION['role'] != 'admin'){
                    echo 
                    "
                        <button name='profile'>Profile</button>
                    ";
                }
                echo"
                            
                            <button name='logout'>Logout</button>
                        </form>
                      </div>";
            }
            ?>
        </div>
    </nav>
<?php } ?>

<style>
    body {
        margin: 0;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
    }

    nav ul,
    nav li,
    nav li a {
        line-height: 1;
        /* รีเซ็ต line-height ที่อาจมาจาก BS5 */
    }

    nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        background-color: #410690;
        padding: 10px 20px;
    }

    nav img {
        margin: 1rem;
    }

    nav ul {
        margin-left: 50px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        padding: 0;
        list-style: none;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    nav li {
        margin: 1rem;
        list-style: none;
        transition: .1s all ease;
    }

    li a {
        text-decoration: none;
        color: rgb(255, 255, 255);
        font-weight: bold;
        font-size: 22px;
        text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.115);
        transition: .1s all ease;
    }

    nav li:hover {
        transform: scale(1.04);
    }

    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        nav {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        nav ul {
            flex-direction: column;
            width: 100%;
            padding: 0;
        }

        nav li {
            width: 100%;
            text-align: center;
        }

        li a {
            font-size: 22px;
        }
    }

    .userProfile {
        color: rgb(255, 255, 255);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
        font-weight: bold;
        text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.115);
        margin-left: auto;
        margin-right: 5rem;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }

    .userProfile h2 {
        font-size: 30px !important;
        font-weight: bold !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .bxs-user-circle {
        font-size: 60px;
        margin: 15px;
        color: rgb(255, 255, 255);
        cursor: pointer;
    }

    .dropdown {
        width: 100px;
        <?php
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
            echo "height: 50px;";
        } else {
            echo "height: 80px;";
        }
        ?>
        background-color: rgb(255, 255, 255);
        position: absolute !important;
        top: 60%;
        right: 30%;
        border: 1px solid rgba(0, 0, 0, 0.107);
        box-shadow: 0 8px 10px rgba(0, 0, 0, 0.4);
        border-radius: 1rem;
        text-align: center;
        display: none;

    }

    .dropdown button {
        margin-top: 12px;
        border: none;
        background-color: white;
        cursor: pointer;
        font-weight: bold;
        font-size: 15px;
        transition: .1s all ease;
    }

    .dropdown button:hover {
        transform: scale(1.02);
        color: red;
    }

    .userProfile:hover .dropdown {
        display: block !important;
    }
</style>