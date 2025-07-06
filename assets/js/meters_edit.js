// assets/js/meters_edit.js
document.addEventListener('DOMContentLoaded', function() {
    const machineSelect = document.getElementById('machine_id');
    const cashGambeeMeterFields = document.getElementById('cashGambeeMeterFields');
    const coinsMachineMeterFields = document.getElementById('coinsMachineMeterFields');
    const offlineMachineStatusSection = document.getElementById('offlineMachineStatusSection'); // Re-added variable

    // Function to toggle visibility of meter fields and offline section
    function toggleMeterForms() {
        const selectedMachineOption = machineSelect.options[machineSelect.selectedIndex];
        const systemComp = selectedMachineOption ? selectedMachineOption.dataset.systemComp : '';
        const machineType = selectedMachineOption ? selectedMachineOption.dataset.machineType : '';

        // Hide all dynamic sections initially
        cashGambeeMeterFields.style.display = 'none';
        coinsMachineMeterFields.style.display = 'none';
        offlineMachineStatusSection.style.display = 'none'; // Control this section

        if (systemComp === 'offline') {
            offlineMachineStatusSection.style.display = 'block'; // Show offline section
            if (machineType === 'COINS') {
                coinsMachineMeterFields.style.display = 'block';
            } else { // CASH or GAMBEE
                cashGambeeMeterFields.style.display = 'block';
            }
        }
    }

    // Attach event listeners
    if (machineSelect) {
        machineSelect.addEventListener('change', toggleMeterForms);
    }

    // Initial call to set correct form state on page load
    toggleMeterForms();
});
