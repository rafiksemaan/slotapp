function validateTrackingForm(form) {
    const trackingDate = form.tracking_date.value;
    
    if (!trackingDate) {
        alert('Please select a tracking date.');
        return false;
    }
    
    // Check if at least one field has data
    const fields = ['slots_drop', 'slots_out', 'gambee_drop', 'gambee_out', 'coins_drop', 'coins_out'];
    let hasData = false;
    
    for (let field of fields) {
        const inputElement = form[field];
        if (inputElement && inputElement.value && parseFloat(inputElement.value) > 0) {
            hasData = true;
            break;
        }
    }
    
    if (!hasData) {
        return confirm('No performance data entered. Are you sure you want to create an entry with all zeros?');
    }
    
    return true;
}

// Auto-focus first field
document.addEventListener('DOMContentLoaded', function () {
    const trackingDateInput = document.getElementById('tracking_date');
    if (trackingDateInput) {
        trackingDateInput.focus();
    }

    const form = document.getElementById('dailyTrackingCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateTrackingForm(this)) {
                event.preventDefault();
            }
        });
    }
});
