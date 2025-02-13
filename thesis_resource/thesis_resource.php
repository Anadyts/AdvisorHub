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
               a.first_name as advisor_fname, 
               a.last_name as advisor_lname,
               ac.role as advisor_role
        FROM advisor_request ar
        LEFT JOIN Advisor a ON ar.advisor_id = a.id
        LEFT JOIN account ac ON a.id = ac.id
        WHERE ar.id = ?";

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
                       FROM Student s 
                       LEFT JOIN account ac ON s.id = ac.id 
                       WHERE s.id = ?";
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
$current_user_id = $_SESSION['username']; // ตอนนี้ได้ค่า "John"
$current_user_role = $_SESSION['role'];

$can_upload = false;
$is_owner = false;

// ดึงรหัสนักศึกษาจากชื่อ
$student_id_query = "SELECT id FROM student WHERE first_name = ?";
$stmt = $conn->prepare($student_id_query);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();
$actual_student_id = $student_data ? $student_data['id'] : null;


// Check if user is advisor of this thesis
if ($current_user_role === 'advisor' && $thesis['advisor_id'] === $current_user_id) {
    $can_upload = true;
    $is_owner = true;
} else {
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
// Check if user is advisor of this thesis
if ($current_user_role === 'advisor') {
    $advisor_id_query = "SELECT id FROM advisor WHERE first_name = ?";
    $stmt = $conn->prepare($advisor_id_query);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $advisor_result = $stmt->get_result();
    $advisor_data = $advisor_result->fetch_assoc();
    $actual_advisor_id = $advisor_data ? $advisor_data['id'] : null;

    if ($actual_advisor_id === $thesis['advisor_id']) {
        $can_upload = true;
        $is_owner = true;
    }
}

// Fetch existing files for this thesis
$files_sql = "SELECT tr.*, ac.role
              FROM thesis_resource tr
              LEFT JOIN account ac ON tr.uploader_id = ac.id
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
        .btn-group > .action-btn {
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
                                <?php echo htmlspecialchars($student['id'] . ' ' . 
                                                          $student['first_name'] . ' ' . 
                                                          $student['last_name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="section-title mb-3">Advisor</div>
                        <div class="section-content mb-3">
                            <?php echo htmlspecialchars($thesis['advisor_fname'] . ' ' . $thesis['advisor_lname']); ?>
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
                <h5 class="section-title mb-4">Uploaded Files</h5>
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
                                    <div class="fw-bold"><?php echo htmlspecialchars($file['file_name']); ?></div>
                                    <small class="text-muted d-block">
                                        Uploaded by: <?php echo htmlspecialchars($file['uploader_id']); ?>
                                    </small>
                                    <small class="text-muted">
                                        Upload time: <?php echo date('M d, Y H:i', strtotime($file['time_stamp'])); ?>
                                    </small>
                                </div>
                                <div class="btn-group">
                                    <div class="btn-group">
                                        <form method="POST" action="download.php" style="display: inline;">
                                            <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                            <button type="submit" class="action-btn download-btn">
                                                <i class="bi bi-download"></i>
                                            </button>
                                        </form>
                                        <?php if ($is_owner): ?>
                                            <button class="action-btn delete-btn" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    <script>
        // ใช้โค้ดเดิมของ script ที่คุณมี
        const uploadForm = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileInput');
        const thesisId = document.getElementById('thesisId').value;
        const progressBar = document.querySelector('.progress-bar');
        const progress = document.querySelector('.progress');

        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const files = fileInput.files;
            if (files.length > 0) {
                Array.from(files).forEach(handleFile);
            } else {
                alert('Please select files to upload');
            }
        });

        function handleFile(file) {
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'application/zip',
                'application/x-rar-compressed',
                'text/plain'
            ];

            if (allowedTypes.includes(file.type)) {
                uploadFile(file);
            } else {
                alert(`File type not allowed: ${file.name}\nAllowed types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, PNG, ZIP, RAR, TXT`);
            }
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('thesis_id', <?php echo $thesis_id; ?>);

            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw response:', text);
                return JSON.parse(text);
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Upload failed: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Upload failed');
            });
        }

        function deleteFile(fileId) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'file_id=' + fileId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Delete failed: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>