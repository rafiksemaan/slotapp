document.addEventListener('DOMContentLoaded', function() {
    // Initialize pie chart for general report
    const resultsPieCtx = document.getElementById('results-pie-chart');
    if (resultsPieCtx) {
        const chartData = JSON.parse(resultsPieCtx.dataset.chartData);
        
        const chartLabels = chartData.map(item => item.type);
        const chartValues = chartData.map(item => item.abs_result);
        const chartColors = chartData.map((item, index) => getChartColor(index));

        new Chart(resultsPieCtx, {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#1e1e1e'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // We'll use custom legend
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: $${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});

// Helper function to generate chart colors (moved from PHP)
function getChartColor(index) {
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];
    return colors[index % colors.length];
}
