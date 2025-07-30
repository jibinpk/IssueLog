// Dashboard specific functionality
let charts = {};

function loadDashboardData() {
    fetch('api/dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data.stats);
                updateDashboardCharts(data.data.charts);
            } else {
                showError('Failed to load dashboard data: ' + data.error);
            }
        })
        .catch(error => {
            showError('Network error: ' + error.message);
        });
}

function updateDashboardStats(stats) {
    document.getElementById('total-logs').textContent = stats.total_logs || 0;
    document.getElementById('open-logs').textContent = stats.open_logs || 0;
    document.getElementById('resolved-logs').textContent = stats.resolved_logs || 0;
    document.getElementById('escalated-logs').textContent = stats.escalated_logs || 0;
    document.getElementById('recurring-logs').textContent = stats.recurring_logs || 0;
    document.getElementById('avg-time').textContent = stats.avg_time || 0;
}

function updateDashboardCharts(chartData) {
    // Destroy existing charts
    Object.values(charts).forEach(chart => {
        if (chart) chart.destroy();
    });
    charts = {};
    
    // Issue Categories Pie Chart
    createCategoriesChart(chartData.categories);
    
    // Plugins Bar Chart
    createPluginsChart(chartData.plugins);
    
    // Time Series Line Chart
    createTimeChart(chartData.time_series);
    
    // Recurring vs Non-recurring Donut Chart
    createRecurringChart(chartData.recurring);
}

function createCategoriesChart(data) {
    const ctx = document.getElementById('categoriesChart');
    if (!ctx) return;
    
    const labels = data.map(item => item.issue_category);
    const values = data.map(item => item.count);
    const colors = generateColors(data.length);
    
    charts.categories = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Roboto',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createPluginsChart(data) {
    const ctx = document.getElementById('pluginsChart');
    if (!ctx) return;
    
    const labels = data.map(item => item.plugin_name);
    const values = data.map(item => item.count);
    
    charts.plugins = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Issues',
                data: values,
                backgroundColor: '#6750a4',
                borderColor: '#6750a4',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            family: 'Roboto'
                        }
                    },
                    grid: {
                        color: '#e7e0ec'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Roboto'
                        },
                        maxRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function createTimeChart(data) {
    const ctx = document.getElementById('timeChart');
    if (!ctx) return;
    
    // Fill in missing dates for the last 30 days
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - 29);
    
    const dateMap = {};
    data.forEach(item => {
        dateMap[item.date] = parseInt(item.count);
    });
    
    const labels = [];
    const values = [];
    
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        values.push(dateMap[dateStr] || 0);
    }
    
    charts.time = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Issues Created',
                data: values,
                borderColor: '#6750a4',
                backgroundColor: 'rgba(103, 80, 164, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            family: 'Roboto'
                        }
                    },
                    grid: {
                        color: '#e7e0ec'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Roboto'
                        },
                        maxTicksLimit: 10
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function createRecurringChart(data) {
    const ctx = document.getElementById('recurringChart');
    if (!ctx) return;
    
    const labels = data.map(item => item.type);
    const values = data.map(item => item.count);
    
    charts.recurring = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#6750a4', '#e8def8'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Roboto',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function generateColors(count) {
    const baseColors = [
        '#6750a4', '#625b71', '#7d5260', '#6b5b95', '#50527a',
        '#9b59b6', '#8e44ad', '#663399', '#5d4e75', '#6c567b'
    ];
    
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(baseColors[i % baseColors.length]);
    }
    
    return colors;
}
