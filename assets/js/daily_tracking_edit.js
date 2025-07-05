// assets/js/daily_tracking_edit.js

import { isRequired, isNumber } from './validation_utils.js';

function validateTrackingForm(form) {
    const rules = {
        tracking_date: [
            { validator: isRequired, message: 'Please select a tracking date.' }
        ],
        slots_drop: [{ validator: (value) => value === '' || isNumber(value), message: 'Slots Drop must be a number.' }],
        slots_out: [{ validator: (value) => value === '' || isNumber(value), message: 'Slots Out must be a number.' }],
        gambee_drop: [{ validator: (value) => value === '' || isNumber(value), message: 'Gambee Drop must be a number.' }],
        gambee_out: [{ validator: (value) => value === '' || isNumber(value), message: 'Gambee Out must be a number.' }],
        coins_drop: [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Drop must be a number.' }],
        coins_out: [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Out must be a number.' }]
    };

    if (!window.validateForm(form, rules)) {
        return false;
    }
    
    // Check if at least one field has data (custom logic)
    const fields = ['slots_drop', 'slots_out', 'gambee_drop', 'gambee_out', 'coins_drop', 'coins_out'];
    let hasData = false;
    
    for (let field of fields) {
        const inputElement = form[field];
        if (inputElement && inputToNumber(inputElement.value) > 0) { // Use helper for number conversion
            hasData = true;
            break;
        }
    }
    
    if (!hasData) {
        return confirm('No performance data entered. Are you sure you want to update an entry with all zeros?');
    }
    
    return true;
}

// Helper to convert input value to number, handling empty string as 0
function inputToNumber(value) {
    return value === '' ? 0 : parseFloat(value);
}

// Auto-focus first field
document.addEventListener('DOMContentLoaded', function () {
    const trackingDateInput = document.getElementById('tracking_date');
    if (trackingDateInput) {
        trackingDateInput.focus();
    }

    const form = document.getElementById('dailyTrackingEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateTrackingForm(this)) {
                event.preventDefault();
            }
        });
    }
});

