// assets/js/brands_create.js

// Import specific validation functions if needed, or rely on main.js's wrapper
import { isRequired } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('brandCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Define validation rules for this form
            const rules = {
                name: [
                    { validator: isRequired, message: 'Brand name is required.' }
                ]
                // Add more rules for other fields if necessary
            };

            // Call the global validateForm function from main.js, passing custom rules
            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

