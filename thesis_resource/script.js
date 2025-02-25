// Upload functionality (ไม่เปลี่ยนแปลง)
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
    formData.append('thesis_id', thesisId);

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

// ลบไฟล์แชท
function deleteFileChat(message_id) {
    if (confirm('Are you sure you want to delete this file?')) {
        fetch('delete_file_chat.php', {
            method: 'POST',
            body: new URLSearchParams({
                'message_id': message_id
            })
        })
        .then(response => response.json()) // แปลงผลลัพธ์เป็น JSON
        .then(data => {
            if (data.success) {
                // ลบไฟล์ออกจาก DOM ทันที
                const fileItem = document.querySelector(`#file-item-${message_id}`);
                if (fileItem) {
                    fileItem.remove();
                }
            } else {
                alert('Failed to delete the file: ' + data.error);
            }
        })
        .catch(error => alert('Error: ' + error)); // จับข้อผิดพลาดจาก fetch
    }
}

// File filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    // Filters for Uploaded Files
    const fileTypeFiltersFiles = document.querySelectorAll('.file-type-filter-files');
    const uploaderFiltersFiles = document.querySelectorAll('.uploader-filter-files');
    const dateFromFiles = document.getElementById('dateFromFiles');
    const dateToFiles = document.getElementById('dateToFiles');
    const resetFiltersFilesBtn = document.getElementById('resetFiltersFiles');

    // Filters for Uploaded Files From Chat
    const fileTypeFiltersChat = document.querySelectorAll('.file-type-filter-chat');
    const uploaderFiltersChat = document.querySelectorAll('.uploader-filter-chat');
    const dateFromChat = document.getElementById('dateFromChat');
    const dateToChat = document.getElementById('dateToChat');
    const resetFiltersChatBtn = document.getElementById('resetFiltersChat');

    // Date validation for Files
    if (dateFromFiles && dateToFiles) {
        dateFromFiles.addEventListener('change', function() {
            if (dateFromFiles.value) dateToFiles.min = dateFromFiles.value;
            else dateToFiles.min = '';
            applyFiltersFiles();
        });
        dateToFiles.addEventListener('change', function() {
            if (dateToFiles.value) dateFromFiles.max = dateToFiles.value;
            else dateFromFiles.max = '';
            applyFiltersFiles();
        });
    }

    // Date validation for Chat
    if (dateFromChat && dateToChat) {
        dateFromChat.addEventListener('change', function() {
            if (dateFromChat.value) dateToChat.min = dateFromChat.value;
            else dateToChat.min = '';
            applyFiltersChat();
        });
        dateToChat.addEventListener('change', function() {
            if (dateToChat.value) dateFromChat.max = dateToChat.value;
            else dateFromChat.max = '';
            applyFiltersChat();
        });
    }

    // Apply filters for Uploaded Files
    function applyFiltersFiles() {
        console.log('=== Applying filters for Uploaded Files ===');
        const selectedFileTypes = Array.from(fileTypeFiltersFiles)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        console.log('Selected file types (Files):', selectedFileTypes);

        const selectedUploaders = Array.from(uploaderFiltersFiles)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        console.log('Selected uploaders (Files):', selectedUploaders);

        const fromDate = dateFromFiles && dateFromFiles.value ? new Date(dateFromFiles.value) : null;
        const toDate = dateToFiles && dateToFiles.value ? new Date(dateToFiles.value) : null;
        console.log('Date range (Files):', { from: fromDate, to: toDate });

        const fileItems = document.querySelectorAll('.file-item');
        console.log('Total file items (Files):', fileItems.length);

        filterItems(fileItems, selectedFileTypes, selectedUploaders, fromDate, toDate);
    }

    // Apply filters for Uploaded Files From Chat
    function applyFiltersChat() {
        console.log('=== Applying filters for Uploaded Files From Chat ===');
        const selectedFileTypes = Array.from(fileTypeFiltersChat)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        console.log('Selected file types (Chat):', selectedFileTypes);

        const selectedUploaders = Array.from(uploaderFiltersChat)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
        console.log('Selected uploaders (Chat):', selectedUploaders);

        const fromDate = dateFromChat && dateFromChat.value ? new Date(dateFromChat.value) : null;
        const toDate = dateToChat && dateToChat.value ? new Date(dateToChat.value) : null;
        console.log('Date range (Chat):', { from: fromDate, to: toDate });

        const fileItems = document.querySelectorAll('.file-item1');
        console.log('Total file items (Chat):', fileItems.length);

        filterItems(fileItems, selectedFileTypes, selectedUploaders, fromDate, toDate);

        // ปรับ Accordion: ซ่อน title ถ้าไม่มีไฟล์ที่แสดง
        const accordionItems = document.querySelectorAll('.accordion-item');
        accordionItems.forEach(item => {
            const visibleFiles = item.querySelectorAll('.file-item1:not(.d-none)');
            if (visibleFiles.length === 0) {
                item.classList.add('d-none');
                console.log(`Hiding accordion item: ${item.getAttribute('data-title')}`);
            } else {
                item.classList.remove('d-none');
                console.log(`Showing accordion item: ${item.getAttribute('data-title')} with ${visibleFiles.length} files`);
            }
        });
    }

    // Common filter function
    function filterItems(items, selectedFileTypes, selectedUploaders, fromDate, toDate) {
        items.forEach((item, index) => {
            try {
                const fileName = item.querySelector('.fw-bold').textContent.trim();
                console.log(`Processing item ${index + 1}: ${fileName}`);

                let uploaderName = "";
                const smallElements = item.querySelectorAll('small');
                for (const el of smallElements) {
                    if (el.textContent.includes('Uploaded by:')) {
                        uploaderName = el.textContent.split('Uploaded by:')[1].trim();
                        console.log(`Uploader: ${uploaderName}`);
                        break;
                    }
                }

                let uploadDate = new Date();
                for (const el of smallElements) {
                    if (el.textContent.includes('Upload time:')) {
                        uploadDate = new Date(el.textContent.split('Upload time:')[1].trim());
                        console.log(`Upload date: ${uploadDate}`);
                        break;
                    }
                }

                let fileType = 'other';
                const lowerFileName = fileName.toLowerCase();
                if (lowerFileName.includes('.pdf')) fileType = 'pdf';
                else if (lowerFileName.includes('.doc') || lowerFileName.includes('.docx')) fileType = 'doc';
                else if (lowerFileName.includes('.ppt') || lowerFileName.includes('.pptx')) fileType = 'ppt';
                else if (lowerFileName.includes('.xls') || lowerFileName.includes('.xlsx')) fileType = 'xls';
                else if (lowerFileName.includes('.jpg') || lowerFileName.includes('.jpeg') || lowerFileName.includes('.png')) fileType = 'jpg';
                else if (lowerFileName.includes('.zip') || lowerFileName.includes('.rar')) fileType = 'zip';
                console.log(`Detected file type: ${fileType}`);

                let matchesFileType = selectedFileTypes.length === 0 || selectedFileTypes.includes(fileType);
                console.log(`Matches file type (${fileType} in ${selectedFileTypes}): ${matchesFileType}`);

                let matchesUploader = selectedUploaders.length === 0 || selectedUploaders.includes(uploaderName);
                console.log(`Matches uploader (${uploaderName} in ${selectedUploaders}): ${matchesUploader}`);

                let matchesDateRange = true;
                if (fromDate) {
                    matchesDateRange = matchesDateRange && uploadDate >= fromDate;
                    console.log(`Matches from date (${uploadDate} >= ${fromDate}): ${matchesDateRange}`);
                }
                if (toDate) {
                    const adjustedToDate = new Date(toDate);
                    adjustedToDate.setDate(adjustedToDate.getDate() + 1);
                    matchesDateRange = matchesDateRange && uploadDate < adjustedToDate;
                    console.log(`Matches to date (${uploadDate} < ${adjustedToDate}): ${matchesDateRange}`);
                }

                const shouldShow = matchesFileType && matchesUploader && matchesDateRange;
                console.log(`Final decision for ${fileName}: ${shouldShow ? 'Show' : 'Hide'}`);

                if (shouldShow) {
                    item.classList.remove('d-none');
                    console.log(`Showing ${fileName} - Removed d-none`);
                } else {
                    item.classList.add('d-none');
                    console.log(`Hiding ${fileName} - Added d-none`);
                }
            } catch (error) {
                console.error(`Error processing item ${index + 1}:`, error);
                item.classList.remove('d-none');
            }
        });
    }

    // Reset filters for Files
    if (resetFiltersFilesBtn) {
        resetFiltersFilesBtn.addEventListener('click', function() {
            console.log('Resetting filters for Uploaded Files');
            fileTypeFiltersFiles.forEach(checkbox => checkbox.checked = false);
            uploaderFiltersFiles.forEach(checkbox => checkbox.checked = false);
            if (dateFromFiles) dateFromFiles.value = '';
            if (dateToFiles) dateToFiles.value = '';
            if (dateFromFiles) dateFromFiles.max = '';
            if (dateToFiles) dateToFiles.min = '';
            document.querySelectorAll('.file-item').forEach(item => {
                item.classList.remove('d-none');
                console.log('Reset - Showing all files in Uploaded Files');
            });
        });
    }

    // Reset filters for Chat
    if (resetFiltersChatBtn) {
        resetFiltersChatBtn.addEventListener('click', function() {
            console.log('Resetting filters for Uploaded Files From Chat');
            fileTypeFiltersChat.forEach(checkbox => checkbox.checked = false);
            uploaderFiltersChat.forEach(checkbox => checkbox.checked = false);
            if (dateFromChat) dateFromChat.value = '';
            if (dateToChat) dateToChat.value = '';
            if (dateFromChat) dateFromChat.max = '';
            if (dateToChat) dateToChat.min = '';
            document.querySelectorAll('.file-item1').forEach(item => {
                item.classList.remove('d-none');
                console.log('Reset - Showing all files in Uploaded Files From Chat');
            });
            document.querySelectorAll('.accordion-item').forEach(item => {
                item.classList.remove('d-none');
                console.log('Reset - Showing all accordion items');
            });
        });
    }

    // Add event listeners with logging
    fileTypeFiltersFiles.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log(`File type checkbox changed: ${checkbox.value} -> ${checkbox.checked}`);
            applyFiltersFiles();
        });
    });
    uploaderFiltersFiles.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log(`Uploader checkbox changed: ${checkbox.value} -> ${checkbox.checked}`);
            applyFiltersFiles();
        });
    });
    fileTypeFiltersChat.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log(`File type checkbox changed (Chat): ${checkbox.value} -> ${checkbox.checked}`);
            applyFiltersChat();
        });
    });
    uploaderFiltersChat.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log(`Uploader checkbox changed (Chat): ${checkbox.value} -> ${checkbox.checked}`);
            applyFiltersChat();
        });
    });
});