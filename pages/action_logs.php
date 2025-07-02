<?php
/**
 * Action Logs Page - Admin Only
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
$allowed_sort_columns = ['created_at', 'user_id', 'action', 'ip_address'];
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
$filter_user_id = $_GET['user_id'] ?? 'all';
$filter_action = $_GET['action'] ?? '';
$filter_ip_address = $_GET['ip_address'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$query_params = [];
$where_clauses = ["1=1"];

// Apply filters
if ($filter_user_id !== 'all') {
    $where_clauses[] = "l.user_id = ?";
    $query_params[] = $filter_user_id;
}
if (!empty($filter_action)) {
    $where_clauses[] = "l.action LIKE ?";
    $query_params[] = "%" . $filter_action . "%";
}
if (!empty($filter_ip_address)) {
    $where_clauses[] = "l.ip_address LIKE ?";
    $query_params[] = "%" . $filter_ip_address . "%";
}
if (!empty($filter_date_from)) {
    $where_clauses[] = "l.created_at >= ?";
    $query_params[] = $filter_date_from . " 00:00:00";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "l.created_at <= ?";
    $query_params[] = $filter_date_to . " 23:59:59";
}

$where_sql = implode(" AND ", $where_clauses);

try {
    // Get total count for pagination
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM logs l WHERE " . $where_sql);
    $count_stmt->execute($query_params);
    $total_logs = $count_stmt->fetchColumn();
    $total_pages = ceil($total_logs / $per_page);

    // Fetch logs
    $query = "
        SELECT l.*, u.username 
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.id
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

} catch (PDOException $e) {
    $error = "Database error: " . htmlspecialchars($e->getMessage());
    $logs = [];
    $all_users = [];
    $total_logs = 0;
    $total_pages = 0;
}

// Function to build sort URL
function build_sort_url($column, $current_sort_column, $current_sort_order, $current_filters) {
    $order = ($current_sort_column === $column && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = array_merge($current_filters, ['page' => 'action_logs', 'sort' => $column, 'order' => $order]);
    return 'index.php?' . http_build_query($params);
}

// Current filters for URL building
$current_filters = [
    'user_id' => $filter_user_id,
    'action' => $filter_action,
    'ip_address' => $filter_ip_address,
    'date_from' => $filter_date_from,
    'date_to' => $filter_date_to
];
?>

<div class="action-logs-page fade-in">
    <!-- Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <h4 style="margin: 0;">Filter Action Logs</h4>
        </div>
        <div class="card-body">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="action_logs">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_column) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">

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
                            <label for="action">Action Contains</label>
                            <input type="text" name="action" id="action" class="form-control" value="<?= htmlspecialchars($filter_action) ?>">
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
                    <a href="index.php?page=action_logs" class="btn btn-danger">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Action Logs (<?= $total_logs ?> entries)</h3>
        </div>
        <div class="card-body p-6">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
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
                                <a href="<?= build_sort_url('user_id', $sort_column, $sort_order, $current_filters) ?>">
                                    User <?= $sort_column == 'user_id' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('action', $sort_column, $sort_order, $current_filters) ?>">
                                    Action <?= $sort_column == 'action' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Details</th>
                            <th class="px-4 py-2 text-left">
                                <a href="<?= build_sort_url('ip_address', $sort_column, $sort_order, $current_filters) ?>">
                                    IP Address <?= $sort_column == 'ip_address' ? ($sort_order == 'ASC' ? '▲' : '▼') : '' ?>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center px-4 py-6">No action logs found for the selected criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?= htmlspecialchars(format_datetime($log['created_at'])) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['username'] ?? 'N/A (ID: ' . $log['user_id'] . ')') ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="px-4 py-2 text-sm text-muted"><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
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
                    $pagination_params = array_merge($current_filters, ['page' => 'action_logs', 'page_num' => $i, 'sort' => $sort_column, 'order' => $sort_order]);
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
