// assets/js/guest_tracking_upload.js

import { isRequired } from './validation_utils.js';

function validateUploadForm(form) {
    const fileInput = form.excel_file;
    const uploadDate = form.upload_date;
    
    const rules = {
        excel_file: [
            { validator: (value, element) => element.files && element.files.length > 0, message: 'Please select a file to upload.' }
        ],
        upload_date: [
            { validator: isRequired, message: 'Please select an upload date.' }
        ]
    };

    if (!window.validateForm(form, rules)) {
        return false;
    }

    const file = fileInput.files[0];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (file.size > maxSize) {
        alert('File size is too large. Maximum size is 10MB.');
        return false;
    }
    
    const allowedExtensions = ['csv', 'xlsx', 'xls'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedExtensions.includes(fileExtension)) {
        alert('Invalid file type. Please upload a CSV or Excel file.');
        return false;
    }
    
    return confirm('Are you sure you want to upload this file? This will add new guest data to the system.');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('guestTrackingUploadForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateUploadForm(this)) {
                event.preventDefault();
            }
        });
    }
});

