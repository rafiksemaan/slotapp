<?php
/**
 * Security Logs Page - Admin Only
 */

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    include 'access_denied.php';
    exit;
}

// Pagination parameters
$page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

// Sorting parameters
$sort_column = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_sort_columns = ['created_at', 'event_type', 'severity', 'ip_address', 'user_id'];
if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'created_at';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Filter parameters
$filter_event_type = $_GET['event_type'] ?? '';
$filter_severity = $_GET['severity'] ?? '';
$filter_ip_address = $_GET['ip_address'] ?? '';
$filter_user_id = $_GET['user_id'] ?? 'all';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$query_params = [];
$where_clauses = ["1=1"];

// Apply filters
if (!empty($filter_event_type)) {
    $where_clauses[] = "sl.event_type LIKE ?";
    $query_params[] = "%" . $filter_event_type . "%";
}
if (!empty($filter_severity)) {
    $where_clauses[] = "sl.severity = ?";
    $query_params[] = $filter_severity;
}
if (!empty($filter_ip_address)) {
    $where_clauses[] = "sl.ip_address LIKE ?";
    $query_params[] = "%" . $filter_ip_address . "%";
}
if ($filter_user_id !== 'all') {
    $where_clauses[] = "sl.user_id = ?";
    $query_params[] = $filter_user_id;
}
if (!empty($filter_date_from)) {
    $where_clauses[] = "sl.created_at >= ?";
    $query_params[] = $filter_date_from . " 00:00:00";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "sl.created_at <= ?";
    $query_params[] = $filter_date_to . " 23:59:59";
}

$where_sql = implode(" AND ", $where_clauses);

try {
    // Get total count for pagination
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM security_logs sl WHERE " . $where_sql);
    $count_stmt->execute($query_params);
    $total_logs = $count_stmt->fetchColumn();
    $total_pages = ceil($total_logs / $per_page);

    // Fetch logs
    $query = "
        SELECT sl.*, u.username 
        FROM security_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        WHERE " . $where_sql . "
        ORDER BY " . $sort_column . " " . $sort_order . "
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute(array_merge($query_params, [$per_page, $offset]));
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all users for filter dropdown
    $users_stmt = $conn->query("SELECT id, username FROM users ORDER BY username");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get distinct event types for filter dropdown
    $event_types_stmt = $conn->query("SELECT DISTINCT event_type FROM security_logs ORDER BY event_type");
    $all_event_types = $event_types_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . htmlspecialchars($e->getMessage()));
    $logs = [];
    $all_users = [];
    $all_event_types = [];
    $total_logs = 0;
    $total_pages = 0;
}

// Function to build sort URL
function build_sort_url($column, $current_sort_column, $current_sort_order, $current_filters) {
    $order = ($current_sort_column === $column && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = array_merge($current_filters, ['page' => 'security_logs', 'sort' => $column, 'order' => $order]);
    return 'index.php?' . http_build_query($params);
}

// Current filters for URL building
$current_filters = [
    'event_type' => $filter_event_type,
    'severity' => $filter_severity,
    'ip_address' => $filter_ip_address,
    'user_id' => $filter_user_id,
    'date_from' => $filter_date_from,
    'date_to' => $filter_date_to
];
?>

<div class="security-logs-page fade-in">
    <!-- Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <h4 style="margin: 0;">Filter Security Logs</h4>
        </div>
        <div class="card-body">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="security_logs">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_column) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="event_type">Event Type</label>
                            <select name="event_type" id="event_type" class="form-control">
                                <option value="">All Event Types</option>
                                <?php foreach ($all_event_types as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>" <?= $filter_event_type === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="severity">Severity</label>
                            <select name="severity" id="severity" class="form-control">
                                <option value="">All Severities</option>
                                <option value="INFO" <?= $filter_severity === 'INFO' ? 'selected' : '' ?>>INFO</option>
                                <option value="WARNING" <?= $filter_severity === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                                <option value="ERROR" <?= $filter_severity === 'ERROR' ? 'selected' : '' ?>>ERROR</option>
                                <option value="CRITICAL" <?= $filter_severity === 'CRITICAL' ? 'selected' : '' ?>>CRITICAL</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="ip_address">IP Address</label>
                            <input type="text" name="ip_address" id="ip_address" class="form-control" value="<?= htmlspecialchars($filter_ip_address) ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="all">All Users</option>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $filter_user_id == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php?page=security_logs" class="btn btn-danger">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Security Logs (<?= $total_logs ?> entries)</h3>
        </div>
        <div class="card-body p-6">
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('created_at', $sort_column, $sort_order, $current_filters) ?>">
                                    Timestamp <?= $sort_column == 'created_at' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('event_type', $sort_column, $sort_order, $current_filters) ?>">
                                    Event Type <?= $sort_column == 'event_type' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('severity', $sort_column, $sort_order, $current_filters) ?>">
                                    Severity <?= $sort_column == 'severity' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Details</th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('ip_address', $sort_column, $sort_order, $current_filters) ?>">
                                    IP Address <?= $sort_column == 'ip_address' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">User Agent</th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('user_id', $sort_column, $sort_order, $current_filters) ?>">
                                    User <?= $sort_column == 'user_id' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center px-4 py-6">No security logs found for the selected criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?= htmlspecialchars(format_datetime($log['created_at'])) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['event_type']) ?></td>
                                    <td class="px-4 py-2">
                                        <span class="status status-<?= strtolower($log['severity']) ?>">
                                            <?= htmlspecialchars($log['severity']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-muted"><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-2 text-sm text-muted"><?= htmlspecialchars($log['user_agent'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['username'] ?? 'N/A (ID: ' . $log['user_id'] . ')') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination mt-6">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    $pagination_params = array_merge($current_filters, ['page' => 'security_logs', 'page_num' => $i, 'sort' => $sort_column, 'order' => $sort_order]);
                    $pagination_url = 'index.php?' . http_build_query($pagination_params);
                    ?>
                    <li>
                        <a href="<?= $pagination_url ?>" class="<?= $i == $page_num ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>
