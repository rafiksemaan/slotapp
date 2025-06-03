/**
 * Main JavaScript for Slot Management System
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize date pickers
    initDatePickers();
    
    // Handle auto-refresh for reports
    initAutoRefresh();
    
    // Initialize chart if chart container exists
    if (document.querySelector('.chart-container')) {
        initCharts();
    }
    
    // Initialize delete confirmations
    initDeleteConfirmations();
});

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'tooltip';
            tooltipEl.textContent = text;
            
            document.body.appendChild(tooltipEl);
            
            const rect = this.getBoundingClientRect();
            tooltipEl.style.top = (rect.top + window.scrollY - tooltipEl.offsetHeight - 10) + 'px';
            tooltipEl.style.left = (rect.left + window.scrollX + (rect.width / 2) - (tooltipEl.offsetWidth / 2)) + 'px';
            
            setTimeout(() => {
                tooltipEl.classList.add('visible');
            }, 10);
            
            this.addEventListener('mouseleave', function onMouseLeave() {
                tooltipEl.classList.remove('visible');
                
                setTimeout(() => {
                    if (tooltipEl.parentNode) {
                        tooltipEl.parentNode.removeChild(tooltipEl);
                    }
                }, 300);
                
                this.removeEventListener('mouseleave', onMouseLeave);
            });
        });
    });
}

/**
 * Initialize date pickers for date input fields
 */
function initDatePickers() {
    // If datepicker library exists, initialize date pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }
}

/**
 * Initialize auto-refresh for reports
 */
function initAutoRefresh() {
    const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
    const refreshRateSelect = document.getElementById('refresh-rate');
    
    if (autoRefreshToggle && refreshRateSelect) {
        let refreshInterval;
        
        autoRefreshToggle.addEventListener('change', function() {
            if (this.checked) {
                const rate = parseInt(refreshRateSelect.value) * 1000; // Convert to milliseconds
                refreshInterval = setInterval(refreshReport, rate);
            } else {
                clearInterval(refreshInterval);
            }
        });
        
        refreshRateSelect.addEventListener('change', function() {
            if (autoRefreshToggle.checked) {
                clearInterval(refreshInterval);
                const rate = parseInt(this.value) * 1000; // Convert to milliseconds
                refreshInterval = setInterval(refreshReport, rate);
            }
        });
    }
}

/**
 * Refresh report data via AJAX
 */
function refreshReport() {
    const reportContainer = document.getElementById('report-results');
    const filterForm = document.getElementById('report-filters');
    
    if (reportContainer && filterForm) {
        // Show loading indicator
        reportContainer.innerHTML = '<div class="loading">Refreshing data...</div>';
        
        // Get form data
        const formData = new FormData(filterForm);
        
        // Create URL with parameters
        const params = new URLSearchParams(formData).toString();
        const url = 'ajax/get_report.php?' + params;
        
        // Fetch report data
        fetch(url)
            .then(response => response.text())
            .then(html => {
                reportContainer.innerHTML = html;
                // Reinitialize charts if needed
                if (document.querySelector('.chart-container')) {
                    initCharts();
                }
            })
            .catch(error => {
                reportContainer.innerHTML = '<div class="alert alert-danger">Error refreshing data: ' + error.message + '</div>';
            });
    }
}

/**
 * Initialize charts
 */
function initCharts() {
    const ctx = document.getElementById('results-chart');
    
    if (ctx && typeof Chart !== 'undefined') {
        // Sample data - in real application, this would come from PHP
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Handpay', 'Ticket', 'Refill', 'Coins Drop', 'Cash Drop'],
                datasets: [{
                    label: 'OUT',
                    backgroundColor: 'rgba(231, 76, 60, 0.7)',
                    data: [12000, 19000, 3000, 0, 0],
                    borderWidth: 1
                }, {
                    label: 'DROP',
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    data: [0, 0, 0, 15000, 25000],
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
                            color: '#a0a0a0'
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
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });
    }
}

/**
 * Initialize delete confirmations
 */
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const confirmMessage = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            
            if (confirm(confirmMessage)) {
                window.location.href = this.getAttribute('href');
            }
        });
    });
}

/**
 * Handle form submission with validation
 * @param {HTMLFormElement} form The form element to validate
 * @returns {boolean} True if valid, false otherwise
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    // Reset previous error messages
    const errorMessages = form.querySelectorAll('.error-message');
    errorMessages.forEach(message => message.remove());
    
    // Check required fields
    requiredFields.forEach(field => {
        field.classList.remove('error');
        
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            
            // Add error message
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = 'This field is required';
            field.parentNode.appendChild(errorMessage);
        }
        
        // Additional validation for specific types
        if (field.getAttribute('type') === 'email' && field.value.trim()) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(field.value)) {
                isValid = false;
                field.classList.add('error');
                
                // Add error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Please enter a valid email address';
                field.parentNode.appendChild(errorMessage);
            }
        }
        
        // Validate IP address
        if (field.classList.contains('ip-address') && field.value.trim()) {
            const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            if (!ipPattern.test(field.value)) {
                isValid = false;
                field.classList.add('error');
                
                // Add error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Please enter a valid IP address';
                field.parentNode.appendChild(errorMessage);
            }
        }
        
        // Validate MAC address
        if (field.classList.contains('mac-address') && field.value.trim()) {
            const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
            if (!macPattern.test(field.value)) {
                isValid = false;
                field.classList.add('error');
                
                // Add error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Please enter a valid MAC address (e.g., 00:1A:2B:3C:4D:5E)';
                field.parentNode.appendChild(errorMessage);
            }
        }
    });
    
    return isValid;
}
