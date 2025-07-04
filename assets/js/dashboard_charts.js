document.addEventListener('DOMContentLoaded', function() {
    // Machine distribution chart
    const machinesCtx = document.getElementById('machines-chart');
    if (machinesCtx) {
        const machineStats = JSON.parse(machinesCtx.dataset.stats);
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
                        text: 'This Year\'s Transactions',
                        color: '#ffffff'
                    }
                }
            }
        });
    }
});
