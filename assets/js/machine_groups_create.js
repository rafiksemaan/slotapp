function validateGroupForm(form) {
    const checkboxes = form.querySelectorAll('input[name="machine_ids[]"]:checked');
    if (checkboxes.length < 2) {
        alert('Please select at least 2 machines for the group.');
        return false;
    }
    return true;
}

// Update selection count
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="machine_ids[]"]');
    const countElement = document.getElementById('count');
    
    function updateCount() {
        const checked = document.querySelectorAll('input[name="machine_ids[]"]:checked');
        if (countElement) {
            countElement.textContent = checked.length;
            
            // Change color based on count
            if (checked.length < 2) {
                countElement.style.color = 'var(--danger-color)';
            } else {
                countElement.style.color = 'var(--success-color)';
            }
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCount);
    });
    
    // Initial count
    updateCount();
});
