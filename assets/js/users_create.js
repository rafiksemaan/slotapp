// assets/js/users_create.js

import { isRequired, isEmail } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const rules = {
                username: [{ validator: isRequired, message: 'Username is required.' }],
                name: [{ validator: isRequired, message: 'Full Name is required.' }],
                email: [
                    { validator: isRequired, message: 'Email is required.' },
                    { validator: isEmail, message: 'Please enter a valid email address.' }
                ],
                password: [{ validator: isRequired, message: 'Password is required.' }],
                role: [{ validator: isRequired, message: 'Role is required.' }],
                status: [{ validator: isRequired, message: 'Status is required.' }]
            };

            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

