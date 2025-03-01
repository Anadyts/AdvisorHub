document.addEventListener('DOMContentLoaded', function() {
    // ดึง totalRecords จาก data attribute (ยังคงใช้เพื่อตรวจสอบกรณี "All")
    const totalRecords = parseInt(document.getElementById('chatData').dataset.totalRecords);

    // ฟังก์ชันเปลี่ยนจำนวนผลลัพธ์ต่อหน้า รวม sortOrder ใน URL
    function changeResultsPerPage(perPage) {
        const sortOrder = document.getElementById('sortOrder').value;
        const finalPerPage = perPage === totalRecords.toString() ? totalRecords : perPage;
        window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=1&results_per_page=${finalPerPage}&sort_order=${sortOrder}`;
    }

    // ฟังก์ชันจัดการการเรียงลำดับ โดยส่ง sortOrder ไปยัง URL
    function sortTitles() {
        const sortOrder = document.getElementById('sortOrder').value;
        window.location.href = `?student_id=${studentId}&advisor_id=${advisorId}&page=1&results_per_page=${resultsPerPage}&sort_order=${sortOrder}`;
    }

    // ผูก event listener กับ #sortOrder และ .results-per-page
    document.getElementById('sortOrder').addEventListener('change', sortTitles);
    document.querySelector('.results-per-page').addEventListener('change', function() {
        changeResultsPerPage(this.value);
    });
});