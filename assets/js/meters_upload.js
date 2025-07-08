// assets/js/meters_upload.js

// No specific validation functions imported here, as the form submission
// will be handled differently (via fetch after XLSX parsing).
// The validation will be more about file type and size before parsing.

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('meterUploadForm');
    const fileInput = document.getElementById('csv_file'); // Renamed to csv_file in HTML, but now handles XLSX
    const uploadDateInput = document.getElementById('upload_date');
    const submitButton = form.querySelector('button[type="submit"]');

    if (form && fileInput && uploadDateInput && submitButton) {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const file = fileInput.files[0];
            const uploadDate = uploadDateInput.value;

            if (!file) {
                alert('Please select a file to upload.');
                return;
            }
            if (!uploadDate) {
                alert('Please select a data operation date.');
                return;
            }

            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                alert('File size is too large. Maximum size is 10MB.');
                return;
            }

            const allowedExtensions = ['xlsx', 'xls']; // Only allow XLSX/XLS files
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
                alert('Invalid file type. Please upload an Excel file (.xlsx, .xls).');
                return;
            }

            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            const reader = new FileReader();

            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });

                    // Assuming the first sheet contains the data
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];

                    // Convert sheet to JSON array of objects
                    const meterData = XLSX.utils.sheet_to_json(worksheet);

                    // Send data to backend via fetch
                    sendDataToBackend(uploadDate, file.name, meterData);

                } catch (error) {
                    alert('Error reading or parsing Excel file: ' + error.message);
                    console.error('XLSX parsing error:', error);
                    resetFormState();
                }
            };

            reader.onerror = function(error) {
                alert('Error reading file: ' + error.message);
                console.error('FileReader error:', error);
                resetFormState();
            };

            reader.readAsArrayBuffer(file);
        });
    }

    function sendDataToBackend(uploadDate, filename, meterData) {
        fetch('pages/meters/upload.php', { // <--- Changed URL here
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' // <--- Added this header
            },
            body: JSON.stringify({
                upload_date: uploadDate,
                filename: filename,
                meter_data: meterData
            }),
        })
        .then(response => response.json()) // Expect JSON response
        .then(data => {
            if (data.success) {
                alert('Upload successful: ' + data.message);
                // Optionally, redirect or update UI
                window.location.reload(); // Reload to show flash message and updated list
            } else {
                alert('Upload failed: ' + data.message);
                if (data.errors && data.errors.length > 0) {
                    console.error('Backend errors:', data.errors);
                    // You might want to display these errors more prominently
                    alert('Details:\n' + data.errors.join('\n'));
                }
            }
        })
        .catch(error => {
            alert('Network error or unexpected response: ' + error.message);
            console.error('Fetch error:', error);
        })
        .finally(() => {
            resetFormState();
        });
    }

    function resetFormState() {
        submitButton.disabled = false;
        submitButton.textContent = 'Upload and Process';
        fileInput.value = ''; // Clear selected file
    }
});
