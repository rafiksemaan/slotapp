// assets/js/meters_create.js

import { isRequired, isNumber } from './validation_utils.js';

function validateMeterForm(form) {
    const rules = {
        machine_id: [
            { validator: isRequired, message: 'Machine is required.' }
        ],
        operation_date: [
            { validator: isRequired, message: 'Operation date is required.' }
        ],
        meter_type: [
            { validator: isRequired, message: 'Meter type is required.' }
        ]
    };

    const machineSelect = form.machine_id;
    const selectedOption = machineSelect.options[machineSelect.selectedIndex];
    const machineType = selectedOption ? selectedOption.dataset.machineType : '';
    const systemComp = selectedOption ? selectedOption.dataset.systemComp : '';

    // Add validation rules based on machine type and system compatibility
    if (machineType === 'COINS') {
        rules.coins_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins In must be a number.' }];
        rules.coins_out = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Out must be a number.' }];
        rules.coins_drop = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Drop must be a number.' }];
        rules.bets_handpay = [{ validator: (value) => value === '' || isNumber(value), message: 'Bets Handpay must be a number.' }];
    } else if (machineType === 'CASH' || machineType === 'GAMBEE') {
        rules.total_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Total In must be a number.' }];
        rules.total_out = [{ validator: (value) => value === '' || isNumber(value), message: 'Total Out must be a number.' }];
        rules.bills_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Bills In must be a number.' }];
        rules.handpay = [{ validator: (value) => value === '' || isNumber(value), message: 'Handpay must be a number.' }];
        rules.jp = [{ validator: (value) => value === '' || isNumber(value), message: 'JP must be a number.' }];
    }

    // If offline, manual_reading_notes might be required or validated
    if (systemComp === 'offline') {
        // Example: rules.manual_reading_notes = [{ validator: isRequired, message: 'Manual reading notes are required for offline machines.' }];
    }

    if (!window.validateForm(form, rules)) {
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('meterCreateForm');
    const machineSelect = document.getElementById('machine_id');
    const meterTypeSelect = document.getElementById('meter_type'); // This might not be needed for the new logic, but keep for now if it affects other parts.

    const cashGambeeMeterFields = document.getElementById('cashGambeeMeterFields');
    const coinsMachineMeterFields = document.getElementById('coinsMachineMeterFields');
    const offlineMachineStatusSection = document.getElementById('offlineMachineStatusSection');

    // Function to reset all dynamic meter fields
    function resetMeterFields() {
        const fieldsToReset = [
            'total_in', 'total_out', 'bills_in', 'handpay', 'jp',
            'coins_in', 'coins_out', 'coins_drop', 'bets_handpay',
            'manual_reading_notes'
        ];
        fieldsToReset.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
            }
        });
    }

    // Function to toggle visibility of meter fields and offline section
    function toggleMeterForms() {
        resetMeterFields(); // Always reset fields when changing selection

        const selectedMachineOption = machineSelect.options[machineSelect.selectedIndex];
        const machineType = selectedMachineOption ? selectedMachineOption.dataset.machineType : '';
        const systemComp = selectedMachineOption ? selectedMachineOption.dataset.systemComp : '';

        // Hide all dynamic sections initially
        cashGambeeMeterFields.style.display = 'none';
        coinsMachineMeterFields.style.display = 'none';
        offlineMachineStatusSection.style.display = 'none';

        if (machineType === 'COINS') {
            coinsMachineMeterFields.style.display = 'block';
            // For coins machines, manual_reading_notes is shown only if system_comp is offline
            if (systemComp === 'offline') {
                offlineMachineStatusSection.style.display = 'block';
            }
        } else if (machineType === 'CASH' || machineType === 'GAMBEE') {
            cashGambeeMeterFields.style.display = 'block';
            if (systemComp === 'offline') {
                offlineMachineStatusSection.style.display = 'block';
            }
        }
        // If no machine is selected or type is unknown, all remain hidden.
    }

    // Attach event listeners
    if (machineSelect) {
        machineSelect.addEventListener('change', toggleMeterForms);
    }
    // The meterTypeSelect change event is no longer directly controlling the main form sections,
    // but it might still be used for other purposes or validation.
    // If it's not needed, it can be removed. For now, keep it but it won't trigger the main form toggle.
    // if (meterTypeSelect) {
    //     meterTypeSelect.addEventListener('change', toggleMeterForms);
    // }

    // Initial call to set correct form state on page load
    toggleMeterForms();

    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateMeterForm(this)) {
                event.preventDefault();
            }
        });
    }
});
