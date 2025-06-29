<?php
/**
 * AJAX endpoint for loading transactions
 * This is a separate file to avoid any HTML output interference
 */

// Start output buffering to catch any errors
ob_start();

// Set error reporting to catch all issues
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session and include required files
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check permissions
$can_edit = has_permission('editor');

try {
    // Pagination parameters
    $page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
    $per_page = 5;
    $offset = ($page_num - 1) * $per_page;

    // Sorting parameters
    $sort_column = $_GET['sort'] ?? 'timestamp';
    $sort_order = $_GET['order'] ?? 'DESC';

    // Validate sort column
    $allowed_columns = ['timestamp', 'machine_number', 'transaction_type', 'amount', 'username'];
    if (!in_array($sort_column, $allowed_columns)) {
        $sort_column = 'timestamp';
    }

    // Validate sort order
    if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
        $sort_order = 'DESC';
    }

    // Filter parameters
    $filter_machine = $_GET['machine'] ?? 'all';
    $date_range_type = $_GET['date_range_type'] ?? 'month';
    $filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
    $filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
    $filter_month = $_GET['month'] ?? date('Y-m');
    $filter_category = $_GET['category'] ?? '';
    $filter_transaction_type = $_GET['transaction_type'] ?? 'all';

    // Calculate start and end dates
    if ($date_range_type === 'range') {
        $start_date = $filter_date_from;
        $end_date = $filter_date_to;
    } else {
        list($year, $month_num) = explode('-', $filter_month);
        $start_date = "$year-$month_num-01";
        $end_date = date("Y-m-t", strtotime($start_date));
    }

    // Build query
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    $query = "SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username
              FROM transactions t
              JOIN machines m ON t.machine_id = m.id
              JOIN transaction_types tt ON t.transaction_type_id = tt.id
              JOIN users u ON t.user_id = u.id
              WHERE t.timestamp BETWEEN ? AND ?";
    
    // Apply filters
    if ($filter_machine !== 'all') {
        $query .= " AND t.machine_id = ?";
        $params[] = $filter_machine;
    }
    if ($filter_category === 'OUT') {
        $query .= " AND tt.category = 'OUT'";
    } elseif ($filter_category === 'DROP') {
        $query .= " AND tt.category = 'DROP'";
    }
    if ($filter_transaction_type !== 'all') {
        $query .= " AND t.transaction_type_id = ?";
        $params[] = $filter_transaction_type;
    }

    // Get total count for pagination
    $count_query = str_replace("SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username", "SELECT COUNT(*)", $query);
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_transactions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $per_page);
    $has_more = $page_num < $total_pages;

    // Map sort columns to actual database columns
    $sort_map = [
        'timestamp' => 't.timestamp',
        'machine_number' => 'm.machine_number',
        'transaction_type' => 'tt.name',
        'amount' => 't.amount',
        'username' => 'u.username'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 't.timestamp';

    // Add sorting and pagination to main query
    $query .= " ORDER BY $actual_sort_column $sort_order LIMIT $per_page OFFSET $offset";

    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clean any output buffer before sending JSON
    ob_clean();

    // Prepare response
    $response = [
        'transactions' => [],
        'has_more' => $has_more,
        'current_page' => $page_num,
        'total_pages' => $total_pages,
        'total_transactions' => $total_transactions,
        'success' => true
    ];
    
    foreach ($transactions as $t) {
        $response['transactions'][] = [
            'id' => $t['id'],
            'timestamp' => htmlspecialchars(format_datetime($t['timestamp'], 'd M Y - H:i:s')),
            'machine_number' => htmlspecialchars($t['machine_number']),
            'transaction_type' => htmlspecialchars($t['transaction_type']),
            'amount' => format_currency($t['amount']),
            'category' => htmlspecialchars($t['category'] ?? 'N/A'),
            'username' => htmlspecialchars($t['username']),
            'notes' => htmlspecialchars($t['notes'] ?? ''),
            'can_edit' => $can_edit
        ];
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    // Clean any output buffer
    ob_clean();
    
    // Return error as JSON
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'has_more' => false,
        'current_page' => $page_num ?? 1,
        'total_pages' => 0,
        'total_transactions' => 0,
        'transactions' => [],
        'success' => false
    ]);
}

exit;
?>