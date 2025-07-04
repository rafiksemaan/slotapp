function validateForm(form) {
    const machineNumber = form.machine_number.value.trim();
    const model = form.model.value.trim();
    const typeId = form.type_id.value;
    const creditValue = form.credit_value.value;
    const ipAddress = form.ip_address ? form.ip_address.value.trim() : '';
    const macAddress = form.mac_address ? form.mac_address.value.trim() : '';

    if (!machineNumber || !form.brand_id.value || !form.game.value || !typeId || !creditValue || !form.status.value || !form.ticket_printer.value || !form.system_comp.value) {
        alert("Please fill out all required fields.");
        return false;
    }

    if (parseFloat(creditValue) <= 0) {
        alert("Credit value must be a positive number.");
        return false;
    }

    if (ipAddress && !isValidIP(ipAddress)) {
        alert("Please enter a valid IP address.");
        return false;
    }

    if (macAddress && !isValidMAC(macAddress)) {
        alert("Please enter a valid MAC address (e.g., 00:1A:2B:3C:4D:5E).");
        return false;
    }

    return true;
}

function isValidIP(ip) {
    const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
    if (!ipPattern.test(ip)) return false;
    return ip.split('.').every(segment => parseInt(segment, 10) >= 0 && parseInt(segment, 10) <= 255);
}

function isValidMAC(mac) {
    const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
    return macPattern.test(mac);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('machineCreateForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
            }
        });
    }
});
