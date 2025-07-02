// Basic client-side security measures
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const strengthDiv = document.getElementById('passwordStrength');
    
    // Disable autocomplete on password field for security
    if (passwordField) {
        passwordField.setAttribute('autocomplete', 'current-password');
    }
    
    // Basic password strength indicator
    if (passwordField && strengthDiv) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (password.length === 0) {
                strengthText = '';
            } else if (strength < 3) {
                strengthText = '⚠️ Weak password';
                strengthClass = 'strength-weak';
            } else if (strength < 5) {
                strengthText = '🔶 Medium password';
                strengthClass = 'strength-medium';
            } else {
                strengthText = '✅ Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.textContent = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        });
    }
    
    // Prevent multiple form submissions
    if (form && loginBtn) {
        form.addEventListener('submit', function() {
            loginBtn.disabled = true;
            loginBtn.textContent = '🔄 Authenticating...';
            
            // Re-enable after 5 seconds in case of error
            setTimeout(function() {
                loginBtn.disabled = false;
                loginBtn.textContent = '🔐 Secure Login';
            }, 5000);
        });
    }
    
    // Clear form on page unload for security
    window.addEventListener('beforeunload', function() {
        if (passwordField) {
            passwordField.value = '';
        }
    });
    
    // Focus on username field
    if (usernameField) {
        usernameField.focus();
    }

    // Disable right-click context menu on login page
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    
    // Disable F12 and other developer tools shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C') ||
            (e.ctrlKey && e.key === 'U')) {
            e.preventDefault();
        }
    });
});
