function validateUploadForm(form) {
    const fileInput = form.excel_file;
    const uploadDate = form.upload_date;
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select a file to upload.');
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
    
    if (!uploadDate || !uploadDate.value) {
        alert('Please select an upload date.');
        return false;
    }
    
    return confirm('Are you sure you want to upload this file? This will add new guest data to the system.');
}
