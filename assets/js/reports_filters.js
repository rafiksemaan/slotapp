// assets/js/reports_filters.js

// No longer needed as logic is in common_utils.js
// document.addEventListener('DOMContentLoaded', function () {
//     const rangeType = document.getElementById('date_range_type');
//     const fromDate = document.getElementById('date_from');
//     const toDate = document.getElementById('date_to');
//     const monthInput = document.getElementById('month');

//     function toggleInputs() {
//         const isRange = rangeType.value === 'range';

//         if (fromDate) fromDate.disabled = !isRange;
//         if (toDate) toDate.disabled = !isRange;
//         if (monthInput) monthInput.disabled = isRange;
//     }

//     if (rangeType) {
//         rangeType.addEventListener('change', toggleInputs);
//     }
//     toggleInputs(); // Initial call
// });

// Toggle filters function - moved to common_utils.js
// function toggleFilters() {
//     const filtersBody = document.getElementById('filters-body');
//     const toggleIcon = document.getElementById('filter-toggle-icon');
    
//     if (filtersBody && toggleIcon) {
//         if (filtersBody.style.display === 'none') {
//             filtersBody.style.display = 'block';
//             toggleIcon.textContent = '▲';
//             // Add smooth animation
//             filtersBody.style.opacity = '0';
//             filtersBody.style.transform = 'translateY(-10px)';
//             setTimeout(() => {
//                 filtersBody.style.transition = 'all 0.3s ease';
//                 filtersBody.style.opacity = '1';
//                 filtersBody.style.transform = 'translateY(0)';
//             }, 10);
//         } else {
//             filtersBody.style.transition = 'all 0.3s ease';
//             filtersBody.style.opacity = '0';
//             filtersBody.style.transform = 'translateY(-10px)';
//             setTimeout(() => {
//                 filtersBody.style.display = 'none';
//                 toggleIcon.textContent = '▼';
//             }, 300);
//         }
//     }
// }
