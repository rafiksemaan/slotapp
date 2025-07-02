document.addEventListener('DOMContentLoaded', function () {
    const dateRangeType = document.getElementById('date_range_type');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function toggleDateInputs() {
        const isRange = dateRangeType.value === 'range';
        
        // Check if elements exist before accessing properties
        if (dateFrom) dateFrom.disabled = !isRange;
        if (dateTo) dateTo.disabled = !isRange;
        if (monthSelect) monthSelect.disabled = isRange;
    }

    if (dateRangeType) {
        dateRangeType.addEventListener('change', toggleDateInputs);
    }
    toggleDateInputs(); // Initial call
});

// Toggle filters function
function toggleFilters() {
    const filtersBody = document.getElementById('filters-body');
    const toggleIcon = document.getElementById('filter-toggle-icon');
    
    if (filtersBody && toggleIcon) {
        if (filtersBody.style.display === 'none') {
            filtersBody.style.display = 'block';
            toggleIcon.textContent = '▲';
            // Add smooth animation
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
}
