// assets/js/import_transactions.js

function validateImportForm(form) {
    const fileInput = form.csv_files;
            // Updated regex for transactions_YYYY-MM-DD_daily_upload.csv
        if (!/transactions_\d{4}-\d{2}-\d{2}_daily_upload\.csv$/i.test(file.name)) {
            alert(`Filename '${file.name}' does not match the expected format (e.g., transactions_YYYY-MM-DD_daily_upload.csv). Please rename your files accordingly.`);
            return false;
        }

    for (let i = 0; i < fileInput.files.length; i++) {
        const file = fileInput.files[i];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (fileExtension !== 'csv') {
            alert(`File '${file.name}' is not a CSV file. Please select only CSV files.`);
            return false;
        }
        if (!/transactions_\d{4}-\d{2}\.csv$/i.test(file.name)) {
            alert(`Filename '${file.name}' does not match the expected format (e.g., transactions_YYYY-MM.csv). Please rename your files accordingly.`);
            return false;
        }
    }

    return confirm('Are you sure you want to import these CSV files? This will add new transaction records to your database.');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importTransactionsForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateImportForm(this)) {
                event.preventDefault();
            }
        });
    }
});
