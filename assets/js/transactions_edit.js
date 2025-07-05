// assets/js/transactions_edit.js

import { isRequired, isPositiveNumber } from './validation_utils.js';

function validateForm(form) {
    const rules = {
        machine_id: [{ validator: isRequired, message: 'Machine is required.' }],
        transaction_type_id: [{ validator: isRequired, message: 'Transaction type is required.' }],
        amount: [
            { validator: isRequired, message: 'Amount is required.' },
            { validator: isPositiveNumber, message: 'Amount must be a positive number.' }
        ],
        timestamp: [{ validator: isRequired, message: 'Date & Time is required.' }]
    };

    // Operation date is only required if admin and input is present
    const operationDateInput = form.querySelector('[name="operation_date"]');
    if (operationDateInput && !operationDateInput.disabled) {
        rules.operation_date = [{ validator: isRequired, message: 'Operation Date is required.' }];
    }

    return window.validateForm(form, rules);
}

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
                }
            }
            // Also run the new validation logic
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    } else {
        // For non-admin users, just attach the new validation logic
        const form = document.getElementById('transactionEditForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!validateForm(this)) {
                    event.preventDefault();
                }
            });
        }
    }
});

