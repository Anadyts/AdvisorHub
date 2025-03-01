// เก็บแถวเริ่มต้นของตารางเพื่อใช้ในการกรอง
const tbody = document.getElementById('chatTable');
let originalRows = Array.from(tbody.getElementsByTagName('tr'));

// ฟังก์ชันกรองตารางตามคำค้นหา
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = originalRows.slice();

    const filteredRows = rows.filter(row => {
        const studentName = row.cells[1].textContent.toLowerCase();
        const advisorName = row.cells[2].textContent.toLowerCase();
        return studentName.includes(searchInput) || advisorName.includes(searchInput);
    });

    // ลบแถวเก่าทั้งหมดและเพิ่มแถวที่กรองแล้ว
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    filteredRows.forEach(row => tbody.appendChild(row));
}

// ฟังก์ชันจัดการการเรียงลำดับ โดยส่งค่า sortOrder ไปยัง URL
function sortTable() {
    const sortOrder = document.getElementById('sortOrder').value;
    window.location.href = `?page=1&results_per_page=${messagesPerPage}&sort_order=${sortOrder}`;
}

// ฟังก์ชันเลือก/ยกเลิกเลือก checkbox ทั้งหมด
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll').checked;
    const checkboxes = document.getElementsByClassName('chatCheckbox');
    for (let checkbox of checkboxes) {
        checkbox.checked = selectAll;
    }
}

// ฟังก์ชันส่งออกแชทที่เลือกเป็น CSV
function exportSelectedChats() {
    const checkboxes = document.getElementsByClassName('chatCheckbox');
    const selectedPairs = [];

    for (let checkbox of checkboxes) {
        if (checkbox.checked) {
            const studentId = checkbox.getAttribute('data-student-id');
            const advisorId = checkbox.getAttribute('data-advisor-id');
            selectedPairs.push({
                student_id: studentId,
                advisor_id: advisorId
            });
        }
    }

    if (selectedPairs.length === 0) {
        alert('Please select at least one chat to export.');
        return;
    }

    // สร้างฟอร์มเพื่อส่งข้อมูลไปยัง export_chat.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_chat.php';
    form.style.display = 'none';

    const pairsInput = document.createElement('input');
    pairsInput.type = 'hidden';
    pairsInput.name = 'selected_pairs';
    pairsInput.value = JSON.stringify(selectedPairs);
    form.appendChild(pairsInput);

    document.body.appendChild(form);
    form.submit();
}

// ฟังก์ชันเปลี่ยนจำนวนผลลัพธ์ต่อหน้า รวม sortOrder ใน URL
function changeResultsPerPage(perPage) {
    const sortOrder = document.getElementById('sortOrder').value;
    window.location.href = `?page=1&results_per_page=${perPage}&sort_order=${sortOrder}`;
}

// โหลดหน้าเว็บครั้งแรก เก็บแถวเริ่มต้น
window.onload = function() {
    originalRows = Array.from(tbody.getElementsByTagName('tr'));
}