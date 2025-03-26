console.log("chart_student_alltime.js loaded!");
document.addEventListener("DOMContentLoaded", function () {
    const chartContainer = document.getElementById("chartContainer2");
    const dataFromPHP = JSON.parse(chartContainer.getAttribute("data-chart"));

    const names = dataFromPHP.map(item => item.advisor_name);
    const counts = dataFromPHP.map(item => item.total_students);

    const ctx = document.getElementById('advisorChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: names,
            datasets: [{
                label: 'จำนวนนิสิตทั้งหมด',
                data: counts,
                backgroundColor: 'mediumpurple'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false }
                }
            },
            plugins: {
                datalabels: {
                    anchor: 'end', 
                    align: 'top', 
                    color: 'black', 
                    font: {
                        weight: 'bold', 
                        size: 12 
                    }
                }
            }
        }, plugins: [ChartDataLabels] 
    });
});