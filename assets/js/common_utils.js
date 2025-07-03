// assets/js/common_utils.js

document.addEventListener('DOMContentLoaded', function () {
    // Function to toggle date inputs based on date range type
    function toggleDateInputs() {
        const dateRangeType = document.getElementById('date_range_type');
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const monthSelect = document.getElementById('month');

        if (dateRangeType) {
            const type = dateRangeType.value;
            if (dateFrom) dateFrom.disabled = (type !== 'range');
            if (dateTo) dateTo.disabled = (type !== 'range');
            if (monthSelect) monthSelect.disabled = (type !== 'month');
        }
    }

    // Initial call and event listener for date range type
    const dateRangeTypeElement = document.getElementById('date_range_type');
    if (dateRangeTypeElement) {
        dateRangeTypeElement.addEventListener('change', toggleDateInputs);
        toggleDateInputs(); // Initial call
    }

    // Reset month/dates logic (from footer_date_logic.js)
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function resetMonth() {
        if (dateFrom && dateTo && monthSelect) {
            if (dateFrom.value || dateTo.value) {
                monthSelect.selectedIndex = 0;
            }
        }
    }

    function resetDates() {
        if (dateFrom && dateTo && monthSelect) {
            if (monthSelect.value) {
                dateFrom.value = '';
                dateTo.value = '';
            }
        }
    }

    if (dateFrom) dateFrom.addEventListener('change', resetMonth);
    if (dateTo) dateTo.addEventListener('change', resetMonth);
    if (monthSelect) monthSelect.addEventListener('change', resetDates);

    // Function to toggle filters section
    // Ensure it's defined only once
    if (typeof window.toggleFilters === 'undefined') {
        window.toggleFilters = function() {
            const filtersBody = document.getElementById('filters-body');
            const toggleIcon = document.getElementById('filter-toggle-icon');
            
            if (filtersBody && toggleIcon) {
                if (filtersBody.style.display === 'none') {
                    filtersBody.style.display = 'block';
                    toggleIcon.textContent = '▲';
                    filtersBody.style.opacity = '0';
                    filtersBody.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        filtersBody.style.transition = 'all 0.3s ease';
                        filtersBody.style.opacity = '1';
                        filtersBody.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    filtersBody.style.transition = 'all 0.3s ease';
                    filtersBody.style.opacity = '0';
                    filtersBody.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        filtersBody.style.display = 'none';
                        toggleIcon.textContent = '▼';
                    }, 300);
                }
            }
        };
    }

    // Attach toggleFilters to relevant elements, ensuring no duplicate listeners
    const filterToggleHeaders = document.querySelectorAll('.filters-container .card-header');
    filterToggleHeaders.forEach(header => {
        // Check if a listener has already been attached to this specific header
        if (!header.dataset.toggleListenerAttached) {
            header.style.cursor = 'pointer'; // Add cursor style
            header.addEventListener('click', window.toggleFilters);
            header.dataset.toggleListenerAttached = 'true'; // Mark as attached
        }
    });
});
