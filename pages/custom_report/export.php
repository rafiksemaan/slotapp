<?php
/**
 * Export handler for custom reports
 * Handles both PDF and Excel exports
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    define('EXPORT_HANDLER', true);
}

// Clear any output buffers to prevent HTML contamination
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffering
ob_start();

// Generate filename
$filename = 'custom_report_' . date('Y-m-d_H-i-s') . ($export_type === 'pdf' ? '.pdf' : '.csv');

// Get the same data as the main report
$available_columns = [
    'machine_number' => 'Machine #',
    'brand_name' => 'Brand',
    'model' => 'Model',
    'machine_type' => 'Machine Type',
    'credit_value' => 'Credit Value',
    'serial_number' => 'Serial Number',
    'manufacturing_year' => 'Manufacturing Year',
    'total_handpay' => 'Total Handpay',
    'total_ticket' => 'Total Ticket',
    'total_refill' => 'Total Refill',
    'total_coins_drop' => 'Total Coins Drop',
    'total_cash_drop' => 'Total Cash Drop',
    'total_out' => 'Total OUT',
    'total_drop' => 'Total DROP',
    'result' => 'Result'
];

// Calculate start/end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Get machines, brands, and groups for display
try {
    $machines_query = "SELECT m.id, m.machine_number, b.name AS brand_name 
                       FROM machines m
                       LEFT JOIN brands b ON m.brand_id = b.id
                       ORDER BY m.machine_number";
    $machines_stmt = $conn->query($machines_query);
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $brands_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groups_stmt = $conn->query("SELECT id, name FROM machine_groups ORDER BY name");
    $machine_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machines = [];
    $brands = [];
    $machine_groups = [];
}

// Build the same query as the main report
try {
    $select_parts = [];
    $join_parts = [];
    $group_by_parts = ['m.id'];
    
    // Always include machine ID and number
    $select_parts[] = "m.id AS machine_id";
    $select_parts[] = "m.machine_number";
    
    // Add selected columns
    if (in_array('brand_name', $selected_columns)) {
        $select_parts[] = "b.name AS brand_name";
        $join_parts[] = "LEFT JOIN brands b ON m.brand_id = b.id";
    }
    
    if (in_array('model', $selected_columns)) {
        $select_parts[] = "m.model";
    }
    
    if (in_array('machine_type', $selected_columns)) {
        $select_parts[] = "mt.name AS machine_type";
        $join_parts[] = "LEFT JOIN machine_types mt ON m.type_id = mt.id";
    }
    
    if (in_array('credit_value', $selected_columns)) {
        $select_parts[] = "m.credit_value";
    }
    
    if (in_array('serial_number', $selected_columns)) {
        $select_parts[] = "m.serial_number";
    }
    
    if (in_array('manufacturing_year', $selected_columns)) {
        $select_parts[] = "m.manufacturing_year";
    }
    
    // Add transaction-related columns
    $has_transactions = false;
    
    if (in_array('total_handpay', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END), 0) AS total_handpay";
        $has_transactions = true;
    }
    
    if (in_array('total_ticket', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Ticket' THEN t.amount ELSE 0 END), 0) AS total_ticket";
        $has_transactions = true;
    }
    
    if (in_array('total_refill', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Refill' THEN t.amount ELSE 0 END), 0) AS total_refill";
        $has_transactions = true;
    }
    
    if (in_array('total_coins_drop', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END), 0) AS total_coins_drop";
        $has_transactions = true;
    }
    
    if (in_array('total_cash_drop', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END), 0) AS total_cash_drop";
        $has_transactions = true;
    }
    
    if (in_array('total_out', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS total_out";
        $has_transactions = true;
    }
    
    if (in_array('total_drop', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) AS total_drop";
        $has_transactions = true;
    }
    
    if (in_array('result', $selected_columns)) {
        $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS result";
        $has_transactions = true;
    }
    
    // Add transaction joins if needed
    if ($has_transactions) {
        $join_parts[] = "LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?";
        $join_parts[] = "LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id";
    }
    
    // Build the complete query
    $query = "SELECT " . implode(", ", $select_parts);
    $query .= " FROM machines m";
    $query .= " " . implode(" ", array_unique($join_parts));
    $query .= " WHERE 1=1";
    
    // Initialize params array
    $params = [];
    
    // Add date filter if transactions are involved
    if ($has_transactions) {
        $params[] = "{$start_date} 00:00:00";
        $params[] = "{$end_date} 23:59:59";
    }
    
    // Apply machine filter
    if ($machine_id !== 'all') {
        $query .= " AND m.id = ?";
        $params[] = $machine_id;
    }
    
    // Apply brand filter
    if ($brand_id !== 'all') {
        $query .= " AND m.brand_id = ?";
        $params[] = $brand_id;
    }
    
    // Apply machine group filter
    if ($machine_group_id !== 'all') {
        $query .= " AND m.id IN (SELECT machine_id FROM machine_group_members WHERE group_id = ?)";
        $params[] = $machine_group_id;
    }
    
    // Add GROUP BY
    $query .= " GROUP BY " . implode(", ", $group_by_parts);
    
    // Add ORDER BY
    $order_column = $sort_column;
    
    // Map sort columns to actual column names in the query
    switch ($sort_column) {
        case 'brand_name':
            $order_column = in_array('brand_name', $selected_columns) ? 'brand_name' : 'm.machine_number';
            break;
        case 'machine_type':
            $order_column = in_array('machine_type', $selected_columns) ? 'machine_type' : 'm.machine_number';
            break;
        case 'machine_number':
        case 'model':
        case 'credit_value':
        case 'serial_number':
        case 'manufacturing_year':
            $order_column = "m.$sort_column";
            break;
        default:
            // For transaction columns, use the alias directly
            if (in_array($sort_column, ['total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                $order_column = $sort_column;
            } else {
                $order_column = 'm.machine_number';
            }
            break;
    }
    
    $query .= " ORDER BY $order_column $sort_order";
    
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $results = [];
    $error = "Database error: " . $e->getMessage();
}

// Calculate totals for export
$totals = [];
if (!empty($results) && !empty($selected_columns)) {
    $monetary_columns = ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'];
    
    foreach ($selected_columns as $column) {
        if (in_array($column, $monetary_columns)) {
            $total = 0;
            foreach ($results as $row) {
                $total += (float)($row[$column] ?? 0);
            }
            $totals[$column] = $total;
        }
    }
}

// Generate report title
$report_title = "Custom Report";
if ($machine_id !== 'all') {
    $selected_machine = null;
    foreach ($machines as $m) {
        if ($m['id'] == $machine_id) {
            $selected_machine = $m;
            break;
        }
    }
    $report_subtitle = "Machine #" . ($selected_machine['machine_number'] ?? 'N/A');
} elseif ($machine_group_id !== 'all') {
    $selected_group = null;
    foreach ($machine_groups as $g) {
        if ($g['id'] == $machine_group_id) {
            $selected_group = $g;
            break;
        }
    }
    $report_subtitle = "Group: " . ($selected_group['name'] ?? 'N/A');
} elseif ($brand_id !== 'all') {
    $selected_brand = null;
    foreach ($brands as $b) {
        if ($b['id'] == $brand_id) {
            $selected_brand = $b;
            break;
        }
    }
    $report_subtitle = "Brand: " . ($selected_brand['name'] ?? 'N/A');
} else {
    $report_subtitle = "All Machines";
}

if ($date_range_type === 'range') {
    $date_subtitle = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} else {
    $date_subtitle = date('F Y', strtotime($month));
}

// Handle export based on type
if ($export_type === 'pdf') {
    // PDF Export
    include 'export_pdf.php';
} elseif ($export_type === 'excel') {
    // Excel Export
    include 'export_excel.php';
} else {
    // Invalid export type
    header("Location: index.php?page=custom_report&error=Invalid export type");
    exit;
}
?>
