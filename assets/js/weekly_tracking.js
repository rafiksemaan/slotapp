// No specific JS for this page, but keeping the file for consistency if needed later.
// The filter logic is handled by the form submission.

// Add event listener for clickable rows
document.addEventListener('DOMContentLoaded', function() {
    const clickableRows = document.querySelectorAll('.weekly-row-clickable');
    
    clickableRows.forEach(row => {
        row.addEventListener('click', function() {
            const startDate = this.dataset.startDate;
           const endDate = this.dataset.endDate;
            
            if (startDate && endDate) {
                window.location.href = `index.php?page=daily_tracking&date_range_type=range&date_from=${startDate}&date_to=${endDate}`;
            }
        });
    });
});