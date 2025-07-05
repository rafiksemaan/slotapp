// assets/js/profile_edit.js

import { isRequired, isEmail } from './validation_utils.js';

function validateProfileForm(form) {
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    const currentPassword = form.current_password.value;

    const rules = {
        name: [{ validator: isRequired, message: 'Full Name is required.' }],
        email: [
            { validator: isRequired, message: 'Email is required.' },
            { validator: isEmail, message: 'Please enter a valid email address.' }
        ]
    };

    if (!window.validateForm(form, rules)) {
        return false;
    }

    // Custom logic for password change
    if (newPassword) {
        if (!currentPassword) {
            alert('Please enter your current password to change your password.');
            form.current_password.focus();
            return false;
        }
        if (newPassword !== confirmPassword) {
            alert('New password and confirmation do not match.');
            form.confirm_password.focus();
            return false;
        }
        if (newPassword.length < 6) {
            alert('New password must be at least 6 characters long.');
            form.new_password.focus();
            return false;
        }
    }
    return true;
}

// Real-time password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        function checkPasswordMatch() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value === confirmPassword.value) {
                    confirmPassword.style.borderColor = 'var(--success-color)';
                } else {
                    confirmPassword.style.borderColor = 'var(--danger-color)';
                }
            } else {
                confirmPassword.style.borderColor = '';
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }

    const form = document.getElementById('profileEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateProfileForm(this)) {
                event.preventDefault();
            }
        });
    }
});

