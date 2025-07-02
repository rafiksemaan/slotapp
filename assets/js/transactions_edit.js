document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for operation date changes (admin only)
    const transactionForm = document.querySelector('.transaction-form');
    const operationDateInput = document.getElementById('operation_date');
    
    // Check if the user is an admin (assuming a data attribute or similar indicates this)
    // For now, we'll assume the PHP logic correctly renders the script block only for admins.
    // If not, you'd need a data attribute like data-is-admin="true" on the form.
    const isAdmin = transactionForm && transactionForm.dataset.isAdmin === 'true'; // Example check
    
    if (isAdmin && transactionForm && operationDateInput) {
        const originalOperationDate = operationDateInput.dataset.originalDate; // Get original date from data attribute
        
        transactionForm.addEventListener('submit', function(e) {
            const newOperationDate = operationDateInput.value;
            
            if (originalOperationDate !== newOperationDate) {
                const confirmMessage = `You are changing the operation date from ${originalOperationDate} to ${newOperationDate}.\n\nThis will affect how this transaction appears in reports and statistics.\n\nAre you sure you want to continue?`;
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
