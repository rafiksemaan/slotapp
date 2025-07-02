document.addEventListener('DOMContentLoaded', function() {
    // Focus on the date input
    const operationDateInput = document.getElementById('operation_date');
    if (operationDateInput) {
        operationDateInput.focus();
    }
    
    // Add confirmation for setting operation day
    const operationDayForm = document.querySelector('.operation-day-form');
    if (operationDayForm && operationDateInput) {
        operationDayForm.addEventListener('submit', function(e) {
            const selectedDate = operationDateInput.value;
            const currentDate = operationDateInput.dataset.currentDate; // Get current date from data attribute
            
            if (selectedDate !== currentDate) {
                const confirmMessage = `Are you sure you want to set the operation day to ${selectedDate}?\n\nThis will affect all new transactions created after this change.`;
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
