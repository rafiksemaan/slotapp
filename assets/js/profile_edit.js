function validateProfileForm(form) {
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    const currentPassword = form.current_password.value;

    // If new password is provided, current password is required
    if (newPassword && !currentPassword) {
        alert('Please enter your current password to change your password.');
        form.current_password.focus();
        return false;
    }

    // If new password is provided, confirmation is required
    if (newPassword && !confirmPassword) {
        alert('Please confirm your new password.');
        form.confirm_password.focus();
        return false;
    }

    // Check password match
    if (newPassword && newPassword !== confirmPassword) {
        alert('New password and confirmation do not match.');
        form.confirm_password.focus();
        return false;
    }

    // Check password strength
    if (newPassword && newPassword.length < 6) {
        alert('New password must be at least 6 characters long.');
        form.new_password.focus();
        return false;
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
});
