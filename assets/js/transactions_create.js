// assets/js/transactions_create.js

import { isRequired, isPositiveNumber } from './validation_utils.js';

function validateForm(form) {
    const rules = {
        machine_id: [{ validator: isRequired, message: 'Machine is required.' }],
        transaction_type_id: [{ validator: isRequired, message: 'Transaction type is required.' }],
        amount: [
            { validator: isRequired, message: 'Amount is required.' },
            { validator: isPositiveNumber, message: 'Amount must be a positive number.' }
        ],
        timestamp: [{ validator: isRequired, message: 'Date & Time is required.' }],
        operation_date: [{ validator: isRequired, message: 'Operation Date is required.' }]
    };

    return window.validateForm(form, rules);
}

// Optional: Auto-focus first field
document.addEventListener('DOMContentLoaded', function () {
    const machineIdInput = document.getElementById('machine_id');
    if (machineIdInput) {
        machineIdInput.focus();
    }

    const form = document.getElementById('transactionCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
            }
        });
    }
});

