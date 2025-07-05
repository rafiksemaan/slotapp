// assets/js/machines_edit.js

// Import specific validation functions from the utility
import { isRequired, isPositiveNumber, isValidIP, isValidMAC } from './validation_utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('machineEditForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Define validation rules for this form
            const rules = {
                machine_number: [
                    { validator: isRequired, message: 'Machine number is required.' }
                ],
                brand_id: [
                    { validator: isRequired, message: 'Brand is required.' }
                ],
                game: [
                    { validator: isRequired, message: 'Game is required.' }
                ],
                type_id: [
                    { validator: isRequired, message: 'Type is required.' }
                ],
                credit_value: [
                    { validator: isRequired, message: 'Credit value is required.' },
                    { validator: isPositiveNumber, message: 'Credit value must be a positive number.' }
                ],
                status: [
                    { validator: isRequired, message: 'Status is required.' }
                ],
                ticket_printer: [
                    { validator: isRequired, message: 'Ticket printer status is required.' }
                ],
                system_comp: [
                    { validator: isRequired, message: 'System compatibility is required.' }
                ],
                ip_address: [
                    { validator: (value) => value === '' || isValidIP(value), message: 'Please enter a valid IP address.' }
                ],
                mac_address: [
                    { validator: (value) => value === '' || isValidMAC(value), message: 'Please enter a valid MAC address (e.g., 00:1A:2B:3C:4D:5E).' }
                ]
            };

            // Call the global validateForm function from main.js, passing custom rules
            if (!window.validateForm(this, rules)) {
                event.preventDefault();
            }
        });
    }
});

