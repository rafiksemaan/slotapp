document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('machineTypeCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Assuming validateForm is a global function from main.js
            if (typeof validateForm === 'function' && !validateForm(this)) {
                event.preventDefault();
            }
        });
    }
});
