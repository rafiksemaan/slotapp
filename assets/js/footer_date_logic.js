document.addEventListener('DOMContentLoaded', function () {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function resetMonth() {
        if (dateFrom.value || dateTo.value) {
            monthSelect.selectedIndex = 0;
        }
    }

    function resetDates() {
        if (monthSelect.value) {
            dateFrom.value = '';
            dateTo.value = '';
        }
    }

    // Check if elements exist before adding event listeners
    if (dateFrom) dateFrom.addEventListener('change', resetMonth);
    if (dateTo) dateTo.addEventListener('change', resetMonth);
    if (monthSelect) monthSelect.addEventListener('change', resetDates);
});
