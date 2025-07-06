document.addEventListener('DOMContentLoaded', function() {
    const machineSelect = document.getElementById('machine_id');
    const cashGambeeMeterFields = document.getElementById('cashGambeeMeterFields');
    const coinsMachineMeterFields = document.getElementById('coinsMachineMeterFields');
    // Removed offlineMachineStatusSection variable

    // Function to toggle visibility of meter fields and offline section
    function toggleMeterForms() {
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
    }

    // Attach event listeners
    if (machineSelect) {
        machineSelect.addEventListener('change', toggleMeterForms);
    }

    // Initial call to set correct form state on page load
    toggleMeterForms();
});

