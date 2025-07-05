// assets/js/url_cleaner.js

document.addEventListener('DOMContentLoaded', function() {
    const urlCleanerData = document.getElementById('url-cleaner-data');
    if (urlCleanerData) {
        const displayMessage = urlCleanerData.dataset.displayMessage;
        const displayError = urlCleanerData.dataset.displayError;

        // Check if either a message or an error was displayed
        if (displayMessage === 'true' || displayError === 'true') {
            // Use a small timeout to ensure the browser has rendered the message before cleaning the URL
            setTimeout(() => {
                window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?(message|error)=[^&]*/g, ''));
            }, 100); // A short delay
        }
    }
});
