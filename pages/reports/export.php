<?php
/**
 * Export handler for reports
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

// Get filter values from URL
$machine_id = $_GET['machine_id'] ?? 'all';
$brand_id = $_GET['brand_id'] ?? 'all';
$machine_group_id = $_GET['machine_group_id'] ?? 'all';
$date_range_type = $_GET['date_range_type'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$month = $_GET['month'] ?? date('Y-m');
$export_type = $_GET['export'] ?? '';

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Initialize variables
$report_data = [];
$filtered_data = [];
$grand_total_out = 0;
$grand_total_drop = 0;
$grand_total_result = 0;
$machines_for_dropdown = []; // Renamed to avoid conflict with $machines in main report logic

// Get brands for display in report title
try {
    $brands_for_title_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands_for_title = $brands_for_title_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $brands_for_title = [];
}

// Get machine groups for display in report title
try {
    $groups_for_title_stmt = $conn->query("SELECT id, name FROM machine_groups ORDER BY name");
    $machine_groups_for_title = $groups_for_title_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machine_groups_for_title = [];
}

// Build query for report data
$query = "
    SELECT 
        m.id AS machine_id,
        m.machine_number,
        mt.name AS machine_type,
        tt.id AS transaction_type_id,
        tt.name AS transaction_type,
        tt.category,
        COALESCE(SUM(t.amount), 0) AS total_amount
    FROM 
        machines m
    LEFT JOIN 
        machine_types mt ON m.type_id = mt.id
    LEFT JOIN 
        transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?
    LEFT JOIN 
        transaction_types tt ON t.transaction_type_id = tt.id
";

$where_clauses = [];
$query_params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];

if ($machine_id != 'all') {
    $where_clauses[] = "m.id = ?";
    $query_params[] = $machine_id;
}

if ($brand_id != 'all') {
    $where_clauses[] = "m.brand_id = ?";
    $query_params[] = $brand_id;
}

if ($machine_group_id != 'all') {
    $query .= " JOIN machine_group_members mgm ON m.id = mgm.machine_id";
    $where_clauses[] = "mgm.group_id = ?";
    $query_params[] = $machine_group_id;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= "
    GROUP BY 
        m.id, tt.id
    ORDER BY 
        m.machine_number
";

try {
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get machines for initializing report_data (all machines that could be in the report)
    $machines_query = "SELECT m.id, m.machine_number, b.name as brand_name, mt.name as type FROM machines m 
                       LEFT JOIN brands b ON m.brand_id = b.id 
                       LEFT JOIN machine_types mt ON m.type_id = mt.id";
    
    $machines_stmt = $conn->query($machines_query); // No filters applied here, get all machines
    $machines_for_dropdown = $machines_stmt->fetchAll(PDO::FETCH_ASSOC); // Used for report title

    // Initialize report_data with default values for all machines
    foreach ($machines_for_dropdown as $machine) {
        $report_data[$machine['id']] = [
            'machine_id' => $machine['id'],
            'machine_number' => $machine['machine_number'],
            'type' => $machine['type'] ?? 'N/A',
            'transactions' => [],
            'total_out' => 0,
            'total_drop' => 0,
            'result' => 0
        ];
    }

    // Fill in transaction data where it exists
    foreach ($results as $row) {
        if (!isset($report_data[$row['machine_id']])) {
            continue; // Skip unknown machines
        }

        if (!is_null($row['transaction_type_id'])) {
            $amount = floatval($row['total_amount']);
            $report_data[$row['machine_id']]['transactions'][] = [
                'type' => $row['transaction_type'],
                'category' => $row['category'],
                'amount' => $amount
            ];

            if ($row['category'] == 'OUT') {
                $report_data[$row['machine_id']]['total_out'] += $amount;
            } elseif ($row['category'] == 'DROP') {
                $report_data[$row['machine_id']]['total_drop'] += $amount;
            }
        }
    }

    // Calculate result for each machine
    foreach ($report_data as &$machine) {
        $machine['result'] = $machine['total_drop'] - $machine['total_out'];
    }
    unset($machine); // Break the reference

    // Apply filters to $report_data to get $filtered_data for export
    $filtered_data = [];
    foreach ($report_data as $machine_entry) {
        $include_machine = true;

        if ($machine_id != 'all' && $machine_entry['machine_id'] != $machine_id) {
            $include_machine = false;
        }
        if ($brand_id != 'all') {
            $machine_brand_id = null;
            foreach ($machines_for_dropdown as $m_dd) {
                if ($m_dd['id'] == $machine_entry['machine_id']) {
                    // Need to fetch brand_id for this machine
                    $brand_stmt = $conn->prepare("SELECT brand_id FROM machines WHERE id = ?");
                    $brand_stmt->execute([$machine_entry['machine_id']]);
                    $machine_brand_id = $brand_stmt->fetchColumn();
                    break;
                }
            }
            if ($machine_brand_id != $brand_id) {
                $include_machine = false;
            }
        }
        if ($machine_group_id != 'all') {
            $group_member_stmt = $conn->prepare("SELECT COUNT(*) FROM machine_group_members WHERE group_id = ? AND machine_id = ?");
            $group_member_stmt->execute([$machine_group_id, $machine_entry['machine_id']]);
            if ($group_member_stmt->fetchColumn() == 0) {
                $include_machine = false;
            }
        }

        if ($include_machine) {
            $filtered_data[] = $machine_entry;
        }
    }


    // Calculate grand totals for the filtered data
    $grand_total_out = array_sum(array_column($filtered_data, 'total_out'));
    $grand_total_drop = array_sum(array_column($filtered_data, 'total_drop'));
    $grand_total_result = $grand_total_drop - $grand_total_out;

} catch (PDOException $e) {
    // Handle error
    $filtered_data = [];
    $grand_total_out = 0;
    $grand_total_drop = 0;
    $grand_total_result = 0;
    // Log the error for debugging
    error_log("Reports Export Database Error: " . $e->getMessage());
}

// Generate report titles
$report_title = "General Report";

if ($machine_id !== 'all') {
    $selected_machine = null;
    foreach ($machines_for_dropdown as $m) {
        if ($m['id'] == $machine_id) {
            $selected_machine = $m;
            break;
        }
    }
    $report_subtitle = "Machine #" . ($selected_machine['machine_number'] ?? 'N/A');
    if ($selected_machine['brand_name']) {
        $report_subtitle .= " (" . $selected_machine['brand_name'] . ")";
    }
} elseif ($machine_group_id !== 'all') {
    $selected_group = null;
    foreach ($machine_groups_for_title as $g) {
        if ($g['id'] == $machine_group_id) {
            $selected_group = $g;
            break;
        }
    }
    $report_subtitle = "Group: " . ($selected_group['name'] ?? 'N/A');
} elseif ($brand_id !== 'all') {
    $selected_brand = null;
    foreach ($brands_for_title as $b) {
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
    include 'reports/export_pdf.php';
} elseif ($export_type === 'excel') {
    include 'reports/export_excel.php';
} else {
    // Invalid export type
    header("Location: index.php?page=reports&error=Invalid export type");
    exit;
}
?>
