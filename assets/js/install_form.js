document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('installForm');
    const installBtn = form ? form.querySelector('button[type="submit"]') : null; // Select the submit button
    
    if (form && installBtn) {
        form.addEventListener('submit', function() {
            installBtn.disabled = true;
            installBtn.textContent = '⏳ Installing...';
        });
    }
});
