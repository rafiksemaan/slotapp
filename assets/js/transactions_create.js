function validateForm(form) {
    const amountInput = form.amount;
    if (amountInput && parseFloat(amountInput.value) <= 0) {
        alert("Amount must be a positive number.");
        return false;
    }
    return true;
}

// Optional: Auto-focus first field
document.addEventListener('DOMContentLoaded', function () {
    const machineIdInput = document.getElementById('machine_id');
    if (machineIdInput) {
        machineIdInput.focus();
    }
});
