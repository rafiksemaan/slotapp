document.addEventListener('DOMContentLoaded', function() {
    // Machine distribution chart
    const machinesCtx = document.getElementById('machines-chart');
    if (machinesCtx) {
        const machineStats = JSON.parse(machinesCtx.dataset.stats); // Assuming stats are passed via data-attribute
        const labels = machineStats.map(stat => stat.status);
        const data = machineStats.map(stat => stat.count);

        new Chart(machinesCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(231, 76, 60, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(52, 152, 219, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Machines by Status',
                        color: '#ffffff'
                    }
                }
            }
        });
    }
    
    // This month's transactions chart
    const transactionsCtx = document.getElementById('transactions-chart');
    if (transactionsCtx) {
        const outData = JSON.parse(transactionsCtx.dataset.outData);
        const outLabels = JSON.parse(transactionsCtx.dataset.outLabels);
        const dropData = JSON.parse(transactionsCtx.dataset.dropData);
        const dropLabels = JSON.parse(transactionsCtx.dataset.dropLabels);

        new Chart(transactionsCtx, {
            type: 'bar',
            data: {
                labels: [...outLabels, ...dropLabels],
                datasets: [{
                    label: 'Amount',
                    data: [...outData, ...dropData],
                    backgroundColor: [
                        ...Array(outLabels.length).fill('rgba(231, 76, 60, 0.7)'),
                        ...Array(dropLabels.length).fill('rgba(46, 204, 113, 0.7)')
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'This Month\'s Transactions',
                        color: '#ffffff'
                    }
                }
            }
        });
    }

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
