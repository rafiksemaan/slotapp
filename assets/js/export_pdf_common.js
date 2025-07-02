window.onload = function() {
    // Show loading message
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-message';
    loadingDiv.innerHTML = '📄 Preparing PDF...<br><small>Save dialog will open shortly</small>';
    document.body.appendChild(loadingDiv);
    
    // Auto-print with save as PDF after a short delay
    setTimeout(function() {
        // Set the document title to the custom filename for the save dialog
        document.title = document.title; // This will be set by PHP
        
        // Trigger print dialog (user can choose "Save as PDF")
        window.print();
        
        // Remove loading message
        loadingDiv.remove();
    }, 1000);
}

// Auto-close window after printing/saving
window.onafterprint = function() {
    setTimeout(function() {
        window.close();
    }, 500);
}

// Fallback: close window if user cancels print dialog
window.addEventListener('beforeunload', function() {
    // This will trigger if user closes the tab/window
});

// Additional method to detect print dialog cancellation
let printDialogOpen = false;
window.addEventListener('focus', function() {
    if (printDialogOpen) {
        // User likely cancelled the print dialog, close window
        setTimeout(function() {
            window.close();
        }, 1000);
    }
});

window.addEventListener('blur', function() {
    printDialogOpen = true;
});
