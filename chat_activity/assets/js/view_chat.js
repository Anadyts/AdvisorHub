document.addEventListener('DOMContentLoaded', function() {
    const totalRecords = parseInt(document.getElementById('chatData').dataset.totalRecords);

    // ฟังก์ชันเปลี่ยนจำนวนผลลัพธ์ต่อหน้า
    function changeResultsPerPage(perPage) {
        const sortOrder = document.getElementById('sortOrder').value;
        const finalPerPage = parseInt(perPage); // ใช้ค่าที่เลือกโดยตรง
        window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=1&results_per_page=${finalPerPage}&sort_order=${sortOrder}&section=${activeSection}`;
    }

    // ฟังก์ชันเรียงลำดับหัวข้อ
    function sortTitles() {
        const sortOrder = document.getElementById('sortOrder').value;
        window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=1&results_per_page=${resultsPerPage}&sort_order=${sortOrder}&section=${activeSection}`;
    }

    // จัดการคลิกปุ่มสถานะ (ก่อน/หลังการอนุมัติ)
    document.querySelectorAll('.topic-status button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.topic-status button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const section = this.dataset.section;
            const sortOrder = document.getElementById('sortOrder').value;
            window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=1&results_per_page=${resultsPerPage}&sort_order=${sortOrder}&section=${section}`;
        });
    });

    // จัดการคลิกเลขหน้าใน pagination
    document.querySelectorAll('.pagination a[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            const sortOrder = document.getElementById('sortOrder').value;
            window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=${page}&results_per_page=${resultsPerPage}&sort_order=${sortOrder}&section=${activeSection}`;
        });
    });

    document.getElementById('sortOrder').addEventListener('change', sortTitles);
    document.querySelector('.results-per-page').addEventListener('change', function() {
        changeResultsPerPage(this.value);
    });
});