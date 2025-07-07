// assets/js/meters_edit.js
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

// Function to update variance displays
function updateVarianceDisplay(inputElementId, latestElementId, varianceElementId) {
    const inputElement = document.getElementById(inputElementId);
    const latestElement = document.getElementById(latestElementId);
    const varianceElement = document.getElementById(varianceElementId);

    if (inputElement && latestElement && varianceElement) {
        const currentValue = parseFloat(inputElement.value) || 0;
        const latestValue = parseFloat(latestElement.textContent); // Use textContent as it's from data-attribute

        if (!isNaN(latestValue)) {
            const variance = currentValue - latestValue;
            varianceElement.textContent = variance.toLocaleString();
            if (variance < 0) {
                varianceElement.classList.remove('positive');
                varianceElement.classList.add('negative');
            } else if (variance > 0) {
                varianceElement.classList.remove('negative');
                varianceElement.classList.add('positive');
            } else {
                varianceElement.classList.remove('positive', 'negative');
            }
        } else {
            varianceElement.textContent = 'N/A';
            varianceElement.classList.remove('positive', 'negative');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const machineSelect = document.getElementById('machine_id');
    const meterEditForm = document.getElementById('meterEditForm');

    const cashGambeeMeterFields = document.getElementById('cashGambeeMeterFields');
    const coinsMachineMeterFields = document.getElementById('coinsMachineMeterFields');
    const onlineMachineMessage = document.getElementById('onlineMachineMessage');

    // Function to toggle visibility of meter fields
    function toggleMeterForms(systemComp, machineType) {
        // Hide all dynamic sections initially
        cashGambeeMeterFields.style.display = 'none';
        coinsMachineMeterFields.style.display = 'none';
        onlineMachineMessage.style.display = 'none';

        // Reset latest and variance displays
        document.getElementById('latest_bills_in').textContent = 'N/A';
        document.getElementById('variance_bills_in').textContent = 'N/A';
        document.getElementById('latest_coins_drop').textContent = 'N/A';
        document.getElementById('variance_coins_drop').textContent = 'N/A';
        document.getElementById('latest_handpay_cash_gambee').textContent = 'N/A';
        document.getElementById('variance_handpay_cash_gambee').textContent = 'N/A';
        document.getElementById('latest_handpay_coins').textContent = 'N/A';
        document.getElementById('variance_handpay_coins').textContent = 'N/A';

        document.querySelectorAll('.variance-value').forEach(el => {
            el.classList.remove('positive', 'negative');
        });

        if (systemComp === 'offline') {
            if (machineType === 'COINS') {
                coinsMachineMeterFields.style.display = 'block';

                // Populate latest readings for Coins machine
                document.getElementById('latest_coins_drop').textContent = machineSelect.options[machineSelect.selectedIndex].dataset.latestCoinsDrop || 'N/A';
                document.getElementById('latest_handpay_coins').textContent = machineSelect.options[machineSelect.selectedIndex].dataset.latestHandpay || 'N/A';

                // Update variances for Coins machine
                updateVarianceDisplay('coins_drop', 'latest_coins_drop', 'variance_coins_drop');
                updateVarianceDisplay('handpay_coins', 'latest_handpay_coins', 'variance_handpay_coins');

            } else { // CASH or GAMBEE
                cashGambeeMeterFields.style.display = 'block';

                // Populate latest readings for Cash/Gambee machine
                document.getElementById('latest_bills_in').textContent = machineSelect.options[machineSelect.selectedIndex].dataset.latestBillsIn || 'N/A';
                document.getElementById('latest_handpay_cash_gambee').textContent = machineSelect.options[machineSelect.selectedIndex].dataset.latestHandpay || 'N/A';

                // Update variances for Cash/Gambee machine
                updateVarianceDisplay('bills_in', 'latest_bills_in', 'variance_bills_in');
                updateVarianceDisplay('handpay_cash_gambee', 'latest_handpay_cash_gambee', 'variance_handpay_cash_gambee');
            }
        } else if (systemComp === 'online') {
            onlineMachineMessage.style.display = 'block';
        }
    }

    // Attach event listeners
    if (machineSelect) {
        machineSelect.addEventListener('change', function() {
            const selectedOption = machineSelect.options[machineSelect.selectedIndex];
            const systemComp = selectedOption.dataset.systemComp;
            const machineType = selectedOption.dataset.machineType;
            toggleMeterForms(systemComp, machineType);
        });
    }

    // Attach input event listeners for real-time variance calculation
    document.getElementById('bills_in')?.addEventListener('input', () => updateVarianceDisplay('bills_in', 'latest_bills_in', 'variance_bills_in'));
    document.getElementById('coins_drop')?.addEventListener('input', () => updateVarianceDisplay('coins_drop', 'latest_coins_drop', 'variance_coins_drop'));
    document.getElementById('handpay_cash_gambee')?.addEventListener('input', () => updateVarianceDisplay('handpay_cash_gambee', 'latest_handpay_cash_gambee', 'variance_handpay_cash_gambee'));
    document.getElementById('handpay_coins')?.addEventListener('input', () => updateVarianceDisplay('handpay_coins', 'latest_handpay_coins', 'variance_handpay_coins'));

    // Initial call to set correct form state on page load
    const initialSystemComp = meterEditForm.dataset.currentSystemComp;
    const initialMeterType = meterEditForm.dataset.currentMeterType;
    toggleMeterForms(initialSystemComp, initialMeterType);

    // On initial load, set the "Latest" values from the form's data attributes
    // and calculate initial variances for the pre-selected machine.
    const initialBillsIn = meterEditForm.dataset.originalBillsIn;
    const initialCoinsDrop = meterEditForm.dataset.originalCoinsDrop;
    const initialHandpay = meterEditForm.dataset.originalHandpay;

    if (initialBillsIn !== undefined) {
        document.getElementById('latest_bills_in').textContent = initialBillsIn;
        updateVarianceDisplay('bills_in', 'latest_bills_in', 'variance_bills_in');
    }
    if (initialCoinsDrop !== undefined) {
        document.getElementById('latest_coins_drop').textContent = initialCoinsDrop;
        updateVarianceDisplay('coins_drop', 'latest_coins_drop', 'variance_coins_drop');
    }
    // Handpay needs to be handled for both cash/gambee and coins types
    if (initialHandpay !== undefined) {
        document.getElementById('latest_handpay_cash_gambee').textContent = initialHandpay;
        document.getElementById('latest_handpay_coins').textContent = initialHandpay;
        updateVarianceDisplay('handpay_cash_gambee', 'latest_handpay_cash_gambee', 'variance_handpay_cash_gambee');
        updateVarianceDisplay('handpay_coins', 'latest_handpay_coins', 'variance_handpay_coins');
    }

    // Attach form submission validation
    if (meterEditForm) {
        meterEditForm.addEventListener('submit', function(event) {
            if (!validateMeterForm(this)) {
                event.preventDefault();
            }
        });
    }
});

