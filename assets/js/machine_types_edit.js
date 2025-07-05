// assets/js/machine_types_edit.js

import { isRequired } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('machineTypeEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const rules = {
                name: [
                    { validator: isRequired, message: 'Machine type name is required.' }
                ]
            };

            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

