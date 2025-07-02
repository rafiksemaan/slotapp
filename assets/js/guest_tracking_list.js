document.addEventListener('DOMContentLoaded', function () {
    const dateRangeType = document.getElementById('date_range_type');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function toggleDateInputs() {
        const type = dateRangeType.value;
        
        if (dateFrom) dateFrom.disabled = type !== 'range';
        if (dateTo) dateTo.disabled = type !== 'range';
        if (monthSelect) monthSelect.disabled = type !== 'month';
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
