// assets/js/transactions_list.js

// Initialize with values passed from PHP
let currentPage = 1; // Always start with page 1 for initial load
let totalPages = initialTotalPages; // Now initialized from PHP
let totalTransactions = initialTotalTransactions; // Now initialized from PHP
let isLoading = false;

// Current filter parameters (initialized from PHP, will be updated by sort/filter actions)
const currentFilters = {
    machine: '',
    date_range_type: 'month',
    date_from: '',
    date_to: '',
    month: '',
    category: '',
    transaction_type: 'all',
    sort: 'operation_date',
    order: 'DESC'
};

// Function to initialize filters from the current URL parameters
function initializeFiltersFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    for (const key in currentFilters) {
        if (urlParams.has(key)) {
            currentFilters[key] = urlParams.get(key);
        }
    }
}

function loadMoreTransactions() {
    if (isLoading || currentPage >= totalPages) return;
    isLoading = true;
    document.getElementById('loading-indicator').classList.remove('hidden');
    document.getElementById('load-more-btn').disabled = true;
    
    const nextPage = currentPage + 1;
    
    // Build the URL parameters for the separate AJAX endpoint
    const params = new URLSearchParams();
    params.set('page_num', nextPage);
    
    // Add all current filters
    Object.keys(currentFilters).forEach(key => {
        params.set(key, currentFilters[key]);
    });
    
    const url = 'pages/transactions/ajax_transactions.php?' + params.toString();
    console.log('Loading URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.log('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Response is not JSON. Got: ' + contentType);
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.error) {
                alert('Error loading transactions: ' + data.error);
                return;
            }
            
            if (data.success && data.transactions) {
                appendTransactions(data.transactions);
                currentPage = data.current_page;
                totalPages = data.total_pages;
                
                // Hide load more button if no more pages
                if (!data.has_more) {
                    document.getElementById('load-more-btn').style.display = 'none';
                }
                
                // Update pagination info
                updatePaginationInfo(data);
            } else {
                console.error('Invalid response format:', data);
                alert('Invalid response format received');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading transactions: ' + error.message);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('loading-indicator').classList.add('hidden');
            document.getElementById('load-more-btn').disabled = false;
        });
}

function appendTransactions(transactions) {
    const tbody = document.getElementById('transactions-tbody');
    
    // Remove "no transactions" row if it exists
    const noTransactionsRow = document.getElementById('no-transactions-row');
    if (noTransactionsRow) {
        noTransactionsRow.remove();
    }
    
    transactions.forEach(t => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-800 transition duration-150';
        row.innerHTML = `
            <td class="px-4 py-2">${t.operation_date}</td>
            <td class="px-4 py-2">${t.machine_number}</td>
            <td class="px-4 py-2">${t.transaction_type}</td>
            <td class="px-4 py-2 text-right">${t.amount}</td>
            <td class="px-4 py-2">${t.category}</td>
            <td class="px-4 py-2">${t.username}</td>
            <td class="px-4 py-2">${t.notes}</td>
            <td class="px-4 py-2 text-right">
                <a href="index.php?page=transactions&action=view&id=${t.id}" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="assets/icons/view2.png"/></span></a>
                ${t.can_edit ? `
                    <a href="index.php?page=transactions&action=edit&id=${t.id}" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="assets/icons/edit.png"/></span></a>
                    <a href="index.php?page=transactions&action=delete&id=${t.id}" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this transaction?"><span class="menu-icon"><img src="assets/icons/delete.png"/></span></a>
                ` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updatePaginationInfo(data) {
    const countElement = document.getElementById('transaction-count');
    if (countElement) {
        const currentCount = document.querySelectorAll('#transactions-tbody tr').length;
        countElement.textContent = `(Showing ${currentCount} of ${data.total_transactions})`;
    }
    
    const paginationInfo = document.getElementById('pagination-info');
    if (paginationInfo) {
        paginationInfo.innerHTML = `Page ${data.current_page} of ${data.total_pages} (${data.total_transactions} total transactions)`;
    }
}

window.sortTransactions = function(column, order) {
    // Update current filters
    currentFilters.sort = column;
    currentFilters.order = order;
    
    // Reset to first page
    currentPage = 1;
    
    // Build URL parameters
    const params = new URLSearchParams();
    params.set('page', 'transactions');
    
    Object.keys(currentFilters).forEach(key => {
        params.set(key, currentFilters[key]);
    });
    
    window.location.href = 'index.php?' + params.toString();
}

// Date range toggle functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeFiltersFromUrl(); // Initialize filters from URL on load

    // Handle form submission to reset pagination
    const filtersForm = document.getElementById('filters-form');
    if (filtersForm) {
        filtersForm.addEventListener('submit', function() {
            currentPage = 1;
        });
    }

    // Initial load of transactions (if not already loaded by PHP)
    // This part is typically handled by PHP rendering the first page,
    // but if you want to ensure it's always loaded via JS, you could call loadMoreTransactions() here
    // after setting initial currentPage and totalPages from PHP.
    // For now, assuming PHP renders the first page.
});


// Attach event listener to Load More button
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreTransactions);
    }

    // Attach event listeners to sortable table headers
    const sortableHeaders = document.querySelectorAll('.sortable-header');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.sortColumn;
            const order = this.dataset.sortOrder;
            window.sortTransactions(column, order);
        });
    });
});

function updatePaginationInfo(data) {
    const countElement = document.getElementById('transaction-count');
    if (countElement) {
        const currentCount = document.querySelectorAll('#transactions-tbody tr').length;
        countElement.textContent = `(Showing ${currentCount} of ${data.total_transactions})`;
    }
    
    const paginationInfo = document.getElementById('pagination-info');
    if (paginationInfo) {
        paginationInfo.innerHTML = `Page ${data.current_page} of ${data.total_pages} (${data.total_transactions} total transactions)`;
    }
}

