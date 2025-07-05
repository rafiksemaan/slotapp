// assets/js/brands_edit.js

import { isRequired } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('brandEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const rules = {
                name: [
                    { validator: isRequired, message: 'Brand name is required.' }
                ]
            };

            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

