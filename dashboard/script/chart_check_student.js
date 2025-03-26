document.addEventListener("DOMContentLoaded", function () {
    // ดึงข้อมูลจาก data attribute ใน HTML
    const chartData = JSON.parse(document.getElementById('chartContainer1').getAttribute('data-chart'));

    const ctx = document.getElementById('advisorPieChart').getContext('2d');

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['มีอาจารย์ที่ปรึกษาแล้ว', 'ยังไม่มีอาจารย์ที่ปรึกษา'],
            datasets: [{
                label: 'จำนวนนิสิต',
                data: [chartData.studentsWithAdvisor, chartData.studentsWithoutAdvisor],  // ใช้ข้อมูลที่ส่งจาก PHP
                backgroundColor: ['mediumpurple', 'orange']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                datalabels: {
                    color: 'white',
                    font: { weight: 'bold', size: 20 },
                    formatter: (value) => `${value} คน`
                },
            }
        },
        plugins: [ChartDataLabels]
    });
});
