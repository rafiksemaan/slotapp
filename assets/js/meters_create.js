// assets/js/meters_create.js

import { isRequired, isNumber } from './validation_utils.js';

function validateMeterForm(form) {
    const machineSelect = form.machine_id;
    const selectedMachineOption = machineSelect.options[machineSelect.selectedIndex];
    const systemComp = selectedMachineOption ? selectedMachineOption.dataset.systemComp : '';
    const machineType = selectedMachineOption ? selectedMachineOption.dataset.machineType : '';

    const rules = {
        machine_id: [
            { validator: isRequired, message: 'Machine is required.' }
        ],
        operation_date: [
            { validator: isRequired, message: 'Operation date is required.' }
        ]
    };

    // Dynamically add rules based on selected machine type and system compatibility
    if (systemComp === 'offline') {
        if (machineType === 'COINS') {
            rules.coins_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins In must be a number.' }];
            rules.coins_out = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Out must be a number.' }];
            rules.coins_drop = [{ validator: (value) => value === '' || isNumber(value), message: 'Coins Drop must be a number.' }];
            rules.bets_coins = [{ validator: (value) => value === '' || isNumber(value), message: 'Bets must be a number.' }];
            rules.handpay_coins = [{ validator: (value) => value === '' || isNumber(value), message: 'Handpay must be a number.' }];
        } else { // CASH or GAMBEE (offline)
            rules.total_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Total In must be a number.' }];
            rules.total_out = [{ validator: (value) => value === '' || isNumber(value), message: 'Total Out must be a number.' }];
            rules.bills_in = [{ validator: (value) => value === '' || isNumber(value), message: 'Bills In must be a number.' }];
            rules.handpay_cash_gambee = [{ validator: (value) => value === '' || isNumber(value), message: 'Handpay must be a number.' }];
            rules.jp = [{ validator: (value) => value === '' || isNumber(value), message: 'JP must be a number.' }];
        }
    }

    if (!window.validateForm(form, rules)) {
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('meterCreateForm');
    const machineSelect = document.getElementById('machine_id');

    const cashGambeeMeterFields = document.getElementById('cashGambeeMeterFields');
    const coinsMachineMeterFields = document.getElementById('coinsMachineMeterFields');
    // Removed offlineMachineStatusSection variable

    // Function to reset all dynamic meter fields
    function resetMeterFields() {
        const fieldsToReset = [
            'total_in', 'total_out', 'bills_in', 'handpay_cash_gambee', 'jp',
            'coins_in', 'coins_out', 'coins_drop', 'bets_coins', 'handpay_coins',
            // Removed 'manual_reading_notes'
            'notes'
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
        const systemComp = selectedMachineOption ? selectedMachineOption.dataset.systemComp : '';
        const machineType = selectedMachineOption ? selectedMachineOption.dataset.machineType : '';

        // Hide all dynamic sections initially
        cashGambeeMeterFields.style.display = 'none';
        coinsMachineMeterFields.style.display = 'none';
        // Removed offlineMachineStatusSection.style.display = 'none';

        if (systemComp === 'offline') {
            // Removed offlineMachineStatusSection.style.display = 'block';
            if (machineType === 'COINS') {
                coinsMachineMeterFields.style.display = 'block';
            } else { // CASH or GAMBEE
                cashGambeeMeterFields.style.display = 'block';
            }
        }
        // If no machine is selected or system_comp is 'online', all remain hidden.
    }

    // Attach event listeners
    if (machineSelect) {
        machineSelect.addEventListener('change', toggleMeterForms);
    }

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

