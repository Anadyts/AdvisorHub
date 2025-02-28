<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require('../server.php');
include('../components/navbar.php');

// Check session and handle redirects
if (isset($_POST['logout'])) {
    session_destroy();
    header('location: /AdvisorHub/login');
}

//ไม่ให้ admin เข้าถึง
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header('location: /AdvisorHub/advisor');
}

if (empty($_SESSION['username'])) {
    header('location: /AdvisorHub/login');
}

if (isset($_POST['profile'])) {
    header('location: /AdvisorHub/profile');
}

// Get thesis details
$thesis_id = isset($_POST['thesis_id']) ? $_POST['thesis_id'] : null;
if (!$thesis_id) {
    header('location: ../thesis_resource_list/thesis_resource_list.php');
    exit;
}

// Fetch thesis data with advisor details
$sql = "SELECT ar.*, 
               a.advisor_first_name, 
               a.advisor_last_name,
               ac.role as advisor_role
        FROM advisor_request ar
        LEFT JOIN advisor a ON ar.advisor_id = a.advisor_id
        LEFT JOIN account ac ON a.advisor_id = ac.account_id
        WHERE ar.advisor_request_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();
$thesis = $result->fetch_assoc();

if (!$thesis) {
    header('location: ../thesis_resource_list/thesis_resource_list.php');
    exit;
}

// Get student details
$student_ids = json_decode($thesis['student_id'], true);
$students = [];
if (is_array($student_ids)) {
    foreach ($student_ids as $student_id) {
        $student_sql = "SELECT s.*, ac.role as student_role 
                       FROM student s 
                       LEFT JOIN account ac ON s.student_id = ac.account_id 
                       WHERE s.student_id = ?";
        $stmt = $conn->prepare($student_sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $student_result = $stmt->get_result();
        if ($student = $student_result->fetch_assoc()) {
            $students[] = $student;
        }
    }
}

// Check if current user has permission to upload
$current_user_id = $_SESSION['username'];
$current_user_role = $_SESSION['role'];

$can_upload = false;
$is_owner = false;

// Get student ID from name
$student_id_query = "SELECT student_id FROM student WHERE student_first_name = ?";
$stmt = $conn->prepare($student_id_query);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();
$actual_student_id = $student_data ? $student_data['student_id'] : null;

// Check if user is advisor of this thesis
if ($current_user_role === 'advisor') {
    $advisor_id_query = "SELECT advisor_id FROM advisor WHERE advisor_first_name = ?";
    $stmt = $conn->prepare($advisor_id_query);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $advisor_result = $stmt->get_result();
    $advisor_data = $advisor_result->fetch_assoc();
    $actual_advisor_id = $advisor_data ? $advisor_data['advisor_id'] : null;

    if ($actual_advisor_id === $thesis['advisor_id']) {
        $can_upload = true;
        $is_owner = true;
    }
}

// Check if user is one of the students of this thesis
if ($current_user_role === 'student' && $actual_student_id) {
    if (is_array($student_ids)) {
        foreach ($student_ids as $id) {
            if ($id === $actual_student_id) {
                $can_upload = true;
                $is_owner = true;
                break;
            }
        }
    }
}

// Fetch existing files for this thesis
$files_sql = "SELECT tr.*, 
              ac.role,
              CASE 
                WHEN ac.role = 'student' THEN (SELECT student_first_name FROM student WHERE student_id = tr.uploader_id)
                WHEN ac.role = 'advisor' THEN (SELECT advisor_first_name FROM advisor WHERE advisor_id = tr.uploader_id)
                ELSE tr.uploader_id
              END AS uploader_name
              FROM thesis_resource tr
              LEFT JOIN account ac ON tr.uploader_id = ac.account_id
              WHERE tr.advisor_request_id = ?
              ORDER BY tr.time_stamp DESC";
$stmt = $conn->prepare($files_sql);
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$files_result = $stmt->get_result();
$files = $files_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Resources</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../Logo.png">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .thesis-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .thesis-card {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            background-color: white;
            border: none;
        }

        .thesis-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
        }

        .thesis-subtitle {
            color: #7f8c8d;
            font-weight: 500;
            margin-bottom: 1.5rem;
            font-size: 1.125rem;
        }

        .section-title {
            color: #34495e;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .section-content {
            color: #2c3e50;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .file-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .file-item1 {
            background-color: #ffffff;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .file-item1:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .upload-card {
            background-color: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #3498db;
        }

        .upload-btn {
            background-color: #3498db;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background-color: #2980b9;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 6px;
            border: none;
            transition: all 0.3s ease;
            padding: 0;
        }

        .download-btn {
            background-color: #2ecc71;
            color: white;
        }

        .download-btn:hover {
            background-color: #27ae60;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .btn-group>.action-btn {
            margin-left: 8px;
        }

        .back-btn {
            background-color: #34495e;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: #2c3e50;
        }

        .title-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .toggle-title {
            cursor: pointer;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .toggle-title.open .toggle-icon {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .thesis-title {
                font-size: 1.25rem;
            }

            .thesis-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php renderNavbar(['home', 'advisor', 'inbox', 'statistics', 'Teams']); ?>

    <div class="container-fluid thesis-container">
        <!-- Thesis Information -->
        <div class="thesis-card mb-4">
            <div class="card-body p-5">
                <h2 class="thesis-title"><?php echo htmlspecialchars($thesis['thesis_topic_thai']); ?></h2>
                <h4 class="thesis-subtitle"><?php echo htmlspecialchars($thesis['thesis_topic_eng']); ?></h4>

                <div class="row">
                    <div class="col-md-6">
                        <div class="section-title mb-3">Students</div>
                        <?php foreach ($students as $student): ?>
                            <div class="section-content mb-2">
                                <?php echo htmlspecialchars($student['student_id'] . ' ' .
                                    $student['student_first_name'] . ' ' .
                                    $student['student_last_name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="section-title mb-3">Advisor</div>
                        <div class="section-content mb-3">
                            <?php echo htmlspecialchars($thesis['advisor_first_name'] . ' ' . $thesis['advisor_last_name']); ?>
                        </div>
                        <div class="section-title mb-3">Semester</div>
                        <div class="section-content">
                            <?php echo htmlspecialchars($thesis['semester'] . '/' . $thesis['academic_year']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($can_upload): ?>
            <!-- File Upload Area -->
            <div class="card upload-card mb-4">
                <div class="card-body p-5">
                    <h5 class="section-title mb-4">Upload Files</h5>
                    <form id="uploadForm">
                        <div class="mb-3">
                            <input type="file" id="fileInput" class="form-control" multiple>
                            <input type="hidden" id="thesisId" value="<?php echo $thesis_id; ?>">
                        </div>
                        <button type="submit" class="btn upload-btn">
                            <i class="bi bi-upload me-2"></i>Upload
                        </button>
                    </form>
                    <div class="progress mt-3" style="display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- File List -->
        <div class="thesis-card">
            <div class="card-body p-5">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Uploaded Files</h5>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#filterCollapseFiles">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>

                    <div class="collapse mb-3" id="filterCollapseFiles">
                        <div class="card card-body">
                            <div class="row">
                                <!-- File Type Filters -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">File Type</h6>
                                    <div class="d-flex flex-wrap">
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="pdf"
                                                id="pdfFilterFiles">
                                            <label class="form-check-label" for="pdfFilterFiles">PDF</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="doc"
                                                id="docFilterFiles">
                                            <label class="form-check-label" for="docFilterFiles">DOC/DOCX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="ppt"
                                                id="pptFilterFiles">
                                            <label class="form-check-label" for="pptFilterFiles">PPT/PPTX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="xls"
                                                id="xlsFilterFiles">
                                            <label class="form-check-label" for="xlsFilterFiles">XLS/XLSX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="jpg"
                                                id="jpgFilterFiles">
                                            <label class="form-check-label" for="jpgFilterFiles">JPEG/PNG</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-files" type="checkbox" value="zip"
                                                id="zipFilterFiles">
                                            <label class="form-check-label" for="zipFilterFiles">ZIP/RAR</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Uploader Filters -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">Uploader</h6>
                                    <div class="d-flex flex-wrap">
                                        <?php foreach ($students as $student): ?>
                                            <div class="form-check me-3 mb-2">
                                                <input class="form-check-input uploader-filter-files" type="checkbox"
                                                    value="<?php echo htmlspecialchars($student['student_first_name']); ?>"
                                                    id="uploaderFiles<?php echo htmlspecialchars($student['student_id']); ?>">
                                                <label class="form-check-label"
                                                    for="uploaderFiles<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <?php echo htmlspecialchars($student['student_first_name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input uploader-filter-files" type="checkbox"
                                                value="<?php echo htmlspecialchars($thesis['advisor_first_name']); ?>"
                                                id="uploaderFiles<?php echo htmlspecialchars($thesis['advisor_id']); ?>">
                                            <label class="form-check-label"
                                                for="uploaderFiles<?php echo htmlspecialchars($thesis['advisor_id']); ?>">
                                                <?php echo htmlspecialchars($thesis['advisor_first_name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Range Filter -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">Date Range</h6>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">From</span>
                                        <input type="date" class="form-control" id="dateFromFiles">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text">To</span>
                                        <input type="date" class="form-control" id="dateToFiles">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button id="resetFiltersFiles" class="btn btn-sm btn-outline-secondary">Reset Filters</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="filesList">
                    <?php if (empty($files)): ?>
                        <div class="text-center text-muted p-4">
                            <i class="bi bi-file-earmark me-2"></i>
                            No files uploaded yet
                        </div>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <div class="file-item p-4 d-flex align-items-center">
                                <i class="bi bi-file-earmark me-4 fs-3"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($file['thesis_resource_file_name']); ?></div>
                                    <small class="text-muted d-block">
                                        Uploaded by:
                                        <?php echo htmlspecialchars($file['uploader_name'] ?: $file['uploader_id']); ?>
                                    </small>
                                    <small class="text-muted">
                                        Upload time: <?php echo date('M d, Y H:i', strtotime($file['time_stamp'])); ?>
                                    </small>
                                </div>
                                <div class="btn-group">
                                    <form method="POST" action="download.php" style="display: inline;">
                                        <input type="hidden" name="file_id" value="<?php echo $file['thesis_resource_id']; ?>">
                                        <button type="submit" class="action-btn download-btn">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </form>
                                    <?php if ($is_owner): ?>
                                        <button class="action-btn delete-btn"
                                            onclick="deleteFile(<?php echo $file['thesis_resource_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- File List -->
        <div class="thesis-card">
            <div class="card-body p-5">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Uploaded Files From Chat</h5>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#filterCollapseChat">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>
                    <div class="collapse mb-3" id="filterCollapseChat">
                        <div class="card card-body">
                            <div class="row">
                                <!-- File Type Filters -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">File Type</h6>
                                    <div class="d-flex flex-wrap">
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="pdf"
                                                id="pdfFilterChat">
                                            <label class="form-check-label" for="pdfFilterChat">PDF</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="doc"
                                                id="docFilterChat">
                                            <label class="form-check-label" for="docFilterChat">DOC/DOCX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="ppt"
                                                id="pptFilterChat">
                                            <label class="form-check-label" for="pptFilterChat">PPT/PPTX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="xls"
                                                id="xlsFilterChat">
                                            <label class="form-check-label" for="xlsFilterChat">XLS/XLSX</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="jpg"
                                                id="jpgFilterChat">
                                            <label class="form-check-label" for="jpgFilterChat">JPEG/PNG</label>
                                        </div>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input file-type-filter-chat" type="checkbox" value="zip"
                                                id="zipFilterChat">
                                            <label class="form-check-label" for="zipFilterChat">ZIP/RAR</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Uploader Filters -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">Uploader</h6>
                                    <div class="d-flex flex-wrap">
                                        <?php foreach ($students as $student): ?>
                                            <div class="form-check me-3 mb-2">
                                                <input class="form-check-input uploader-filter-chat" type="checkbox"
                                                    value="<?php echo htmlspecialchars($student['student_first_name']); ?>"
                                                    id="uploaderChat<?php echo htmlspecialchars($student['student_id']); ?>">
                                                <label class="form-check-label"
                                                    for="uploaderChat<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <?php echo htmlspecialchars($student['student_first_name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="form-check me-3 mb-2">
                                            <input class="form-check-input uploader-filter-chat" type="checkbox"
                                                value="<?php echo htmlspecialchars($thesis['advisor_first_name']); ?>"
                                                id="uploaderChat<?php echo htmlspecialchars($thesis['advisor_id']); ?>">
                                            <label class="form-check-label"
                                                for="uploaderChat<?php echo htmlspecialchars($thesis['advisor_id']); ?>">
                                                <?php echo htmlspecialchars($thesis['advisor_first_name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Range Filter -->
                                <div class="col-md-4">
                                    <h6 class="mb-2">Date Range</h6>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">From</span>
                                        <input type="date" class="form-control" id="dateFromChat">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text">To</span>
                                        <input type="date" class="form-control" id="dateToChat">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button id="resetFiltersChat" class="btn btn-sm btn-outline-secondary">Reset Filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Prepare student IDs and advisor ID
                $student_ids = json_decode($thesis['student_id'], true);
                $advisor_id = $thesis['advisor_id'];
                $current_user_id = $_SESSION['account_id'];
                $current_user_role = $_SESSION['role'];

                // Define titles SQL based on role
                if ($current_user_role === 'student') {
                    $titles_sql = "SELECT DISTINCT message_title 
                                    FROM messages 
                                    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
                                    AND message_file_name IS NOT NULL 
                                    GROUP BY message_title 
                                    ORDER BY MAX(time_stamp) DESC";
                    $stmt = $conn->prepare($titles_sql);
                    $stmt->bind_param("iiii", $current_user_id, $advisor_id, $advisor_id, $current_user_id);
                } else if ($current_user_role === 'advisor') {
                    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                    $titles_sql = "SELECT DISTINCT message_title 
                                    FROM messages 
                                    WHERE ((sender_id = ? AND receiver_id IN ($placeholders)) 
                                        OR (sender_id IN ($placeholders) AND receiver_id = ?)) 
                                    AND message_file_name IS NOT NULL 
                                    GROUP BY message_title 
                                    ORDER BY MAX(time_stamp) DESC";
                    $stmt = $conn->prepare($titles_sql);
                    $params = array_merge([$current_user_id], $student_ids, $student_ids, [$current_user_id]);
                    $types = str_repeat('i', count($params));
                    $stmt->bind_param($types, ...$params);
                }

                $stmt->execute();
                $titles_result = $stmt->get_result();
                $titles = $titles_result->fetch_all(MYSQLI_ASSOC);
                ?>

                <div class="accordion" id="chatFilesAccordion">
                    <?php foreach ($titles as $index => $title): ?>
                        <?php
                        if ($current_user_role === 'student') {
                            $message_files_sql = "SELECT messages.*, account.role,
                                CASE 
                                    WHEN account.role = 'student' THEN (SELECT student_first_name FROM student WHERE student_id = messages.sender_id)
                                    WHEN account.role = 'advisor' THEN (SELECT advisor_first_name FROM advisor WHERE advisor_id = messages.sender_id)
                                    ELSE messages.sender_id
                                END AS uploader_name
                                FROM messages
                                LEFT JOIN account ON messages.sender_id = account.account_id
                                WHERE ((messages.sender_id = ? AND messages.receiver_id = ?) 
                                    OR (messages.sender_id = ? AND messages.receiver_id = ?)) 
                                AND messages.message_file_name IS NOT NULL
                                AND messages.message_title = ?
                                ORDER BY messages.time_stamp DESC";
                            $stmt = $conn->prepare($message_files_sql);
                            $stmt->bind_param("iiiis", $current_user_id, $advisor_id, $advisor_id, $current_user_id, $title['message_title']);
                        } else if ($current_user_role === 'advisor') {
                            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                            $message_files_sql = "SELECT messages.*, account.role,
                                CASE 
                                    WHEN account.role = 'student' THEN (SELECT student_first_name FROM student WHERE student_id = messages.sender_id)
                                    WHEN account.role = 'advisor' THEN (SELECT advisor_first_name FROM advisor WHERE advisor_id = messages.sender_id)
                                    ELSE messages.sender_id
                                END AS uploader_name
                                FROM messages
                                LEFT JOIN account ON messages.sender_id = account.account_id
                                WHERE ((messages.sender_id = ? AND messages.receiver_id IN ($placeholders)) 
                                    OR (messages.sender_id IN ($placeholders) AND messages.receiver_id = ?)) 
                                AND messages.message_file_name IS NOT NULL
                                AND messages.message_title = ?
                                ORDER BY messages.time_stamp DESC";
                            $stmt = $conn->prepare($message_files_sql);
                            $params = array_merge([$current_user_id], $student_ids, $student_ids, [$current_user_id, $title['message_title']]);
                            $types = str_repeat('i', count($student_ids) * 2 + 2) . 's';
                            $stmt->bind_param($types, ...$params);
                        }

                        $stmt->execute();
                        $messages_files_result = $stmt->get_result();
                        $messages_files = $messages_files_result->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <div class="accordion-item title-container" data-title="<?php echo htmlspecialchars($title['message_title']); ?>">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>"
                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                    aria-controls="collapse<?php echo $index; ?>">
                                    Title: <?php echo htmlspecialchars($title['message_title']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>"
                                class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#chatFilesAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($messages_files)): ?>
                                        <div class="text-center text-muted p-4">
                                            <i class="bi bi-file-earmark me-2"></i>
                                            No files in this title
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages_files as $file): ?>
                                            <div class="file-item1 p-4 d-flex align-items-center w-100 mb-2 mt-2" id="file-item-<?php echo htmlspecialchars($file['message_id']); ?>">
                                                <i class="bi bi-file-earmark me-4 fs-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($file['message_file_name']); ?></div>
                                                    <small class="text-muted d-block">
                                                        Uploaded by:
                                                        <?php echo htmlspecialchars($file['uploader_name'] ?: $file['uploader_id']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        Upload time: <?php echo date('M d, Y H:i', strtotime($file['time_stamp'])); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group">
                                                    <form method="POST" action="download.php" style="display: inline;">
                                                        <input type="hidden" name="is_message" value="1">
                                                        <input type="hidden" name="file_id" value="<?php echo $file['message_id']; ?>">
                                                        <button type="submit" class="action-btn download-btn">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                    </form>
                                                    <?php if ($is_owner): ?>
                                                        <button class="action-btn delete-btn"
                                                            onclick="deleteFileChat(<?php echo htmlspecialchars($file['message_id']); ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <form method="POST" action="../thesis_resource_list/thesis_resource_list.php">
                <button type="submit" class="btn back-btn">
                    <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>

</body>

</html>