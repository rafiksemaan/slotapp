// assets/js/users_edit.js

import { isRequired, isEmail } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const rules = {
                name: [{ validator: isRequired, message: 'Full Name is required.' }],
                email: [
                    { validator: isRequired, message: 'Email is required.' },
                    { validator: isEmail, message: 'Please enter a valid email address.' }
                ],
                role: [{ validator: isRequired, message: 'Role is required.' }],
                status: [{ validator: isRequired, message: 'Status is required.' }]
            };

            // Password field is optional for edit, so only validate if filled
            const passwordField = form.querySelector('[name="password"]');
            if (passwordField && passwordField.value.trim() !== '') {
                rules.password = [{ validator: isRequired, message: 'Password cannot be empty if changing.' }];
            }

            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

