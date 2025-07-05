// assets/js/main.js

/**
 * Main JavaScript for Slot Management System
 */

// Import the new validation utility functions
import { validateForm as validateFormUtility, isRequired, isEmail, isValidIP, isValidMAC } from './validation_utils.js';

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize date pickers
    initDatePickers();
    
    // Handle auto-refresh for reports
    initAutoRefresh();
    
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
 * General form validation function.
 * This function now acts as a wrapper, primarily checking required fields
 * and delegating more complex validation to the new utility.
 * Individual form scripts should define their specific rules and call this.
 * @param {HTMLFormElement} form The form element to validate.
 * @param {Object} customRules Optional. An object defining custom validation rules for fields.
 * @returns {boolean} True if valid, false otherwise.
 */
window.validateForm = (form, customRules = {}) => {
    let isValid = true;

    // Clear all existing errors from previous runs
    form.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
        const errorMessageElement = el.nextElementSibling;
        if (errorMessageElement && errorMessageElement.classList.contains('error-message')) {
            errorMessageElement.remove();
        }
    });

    // Basic check for HTML5 required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!isRequired(field.value)) {
            displayError(field, 'This field is required');
            isValid = false;
        }
    });

    // Delegate to the new utility for custom rules
    if (!validateFormUtility(form, customRules)) {
        isValid = false;
    }
    
    return isValid;
};

/**
 * Helper function to display an error message (moved from original validateForm)
 * @param {HTMLElement} inputElement - The input field.
 * @param {string} message - The error message to display.
 */
const displayError = (inputElement, message) => {
    inputElement.classList.add('error');
    let errorMessageElement = inputElement.nextElementSibling;
    if (!errorMessageElement || !errorMessageElement.classList.contains('error-message')) {
        errorMessageElement = document.createElement('div');
        errorMessageElement.classList.add('error-message');
        inputElement.parentNode.insertBefore(errorMessageElement, inputElement.nextSibling);
    }
    errorMessageElement.textContent = message;
};

