<?php
/**
 * Regional Coordinator Dashboard
 * Shows maintenance calls filtered by coordinator's region
 */

require_once 'bootstrap.php';
require_once 'config/database.php';

// Initialize session
$session = new SessionManager();

// Require coordinator role
$session->requireAuth('coordinator');

// Get coordinator data from session
$coordinator_data = $_SESSION['coordinator_data'] ?? null;

if (!$coordinator_data) {
    header('Location: dashboard.php');
    exit;
}

$coordinator_region = $coordinator_data['region'];
$coordinator_region = $coordinator_data['region'];
$coordinator_name = $coordinator_data['name'];
$provinces = explode(',', $coordinator_data['provinces']);

$page_title = "Regional Dashboard - {$coordinator_region}";

// Get filter parameters
$filter_province = $_GET['province'] ?? '';
$filter_call_type = $_GET['call_type'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Initialize variables
$calls = [];
$stats = [
    'total_calls' => 0,
    'open_calls' => 0,
    'assigned_calls' => 0,
    'in_progress_calls' => 0,
    'resolved_calls' => 0,
    'closed_calls' => 0,
    'avg_response_time' => null
];
$call_types = [];
$error_message = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Build SQL WHERE clauses for regional filtering
    $where_clauses = ["mc.region = ?"];
    $params = [$coordinator_region];
    
    if ($filter_province && in_array($filter_province, $provinces)) {
        $where_clauses[] = "mc.province = ?";
        $params[] = $filter_province;
    }
    
    if ($filter_call_type) {
        $where_clauses[] = "mc.call_type = ?";
        $params[] = $filter_call_type;
    }
    
    if ($filter_priority) {
        $where_clauses[] = "pl.name = ?";
        $params[] = $filter_priority;
    }
    
    if ($filter_status) {
        $where_clauses[] = "mc.status = ?";
        $params[] = $filter_status;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    // Get statistics for this region
    $stats_sql = "
        SELECT 
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_calls,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_calls,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_calls,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_calls,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_calls,
            AVG(CASE WHEN response_time_minutes IS NOT NULL THEN response_time_minutes ELSE NULL END) as avg_response_time
        FROM maintenance_calls mc
        WHERE mc.region = ?
    ";
    
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$coordinator_region]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get maintenance calls for this region
    $calls_sql = "
        SELECT 
            mc.*,
            d.name as department_name,
            pl.name as priority_name,
            pl.color_code as priority_color,
            reporter.first_name as reporter_first_name,
            reporter.last_name as reporter_last_name,
            reporter.email as reporter_email,
            assignee.first_name as assignee_first_name,
            assignee.last_name as assignee_last_name
        FROM maintenance_calls mc
        LEFT JOIN departments d ON mc.department_id = d.id
        LEFT JOIN priority_levels pl ON mc.priority_id = pl.id
        LEFT JOIN users reporter ON mc.reported_by = reporter.id
        LEFT JOIN users assignee ON mc.assigned_to = assignee.id
        WHERE {$where_sql}
        ORDER BY 
            CASE pl.name
                WHEN 'Critical' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Medium' THEN 3
                WHEN 'Low' THEN 4
                ELSE 5
            END,
            mc.created_at DESC
        LIMIT 100
    ";
    
    $calls_stmt = $pdo->prepare($calls_sql);
    $calls_stmt->execute($params);
    $calls = $calls_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique values for filters
    $call_types_stmt = $pdo->query("SELECT DISTINCT call_type FROM maintenance_calls WHERE region = '{$coordinator_region}' AND call_type IS NOT NULL ORDER BY call_type");
    $call_types = $call_types_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $error_message = "Error loading regional data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Regional Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">
                                <i class="fas fa-map-marked-alt text-primary"></i>
                                <?php echo htmlspecialchars($coordinator_region); ?> Region
                            </h3>
                            <p class="text-muted mb-0">
                                Regional Coordinator: <?php echo htmlspecialchars($coordinator_name); ?><br>
                                <small>Provinces: <?php echo htmlspecialchars($coordinator_data['provinces']); ?></small>
                            </p>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0"><?php echo number_format($stats['total_calls']); ?></h4>
                            <small class="text-muted">Total Calls</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-inbox fa-2x text-danger mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['open_calls']; ?></h4>
                    <small class="text-muted">Open</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['assigned_calls']; ?></h4>
                    <small class="text-muted">Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-2x text-info mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['in_progress_calls']; ?></h4>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['resolved_calls']; ?></h4>
                    <small class="text-muted">Resolved</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <i class="fas fa-archive fa-2x text-secondary mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['closed_calls']; ?></h4>
                    <small class="text-muted">Closed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                    <h4 class="mb-0"><?php echo $stats['avg_response_time'] ? round($stats['avg_response_time']) : 'N/A'; ?></h4>
                    <small class="text-muted">Avg Response (min)</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Province</label>
                    <select name="province" class="form-select">
                        <option value="">All Provinces</option>
                        <?php foreach ($provinces as $prov): ?>
                            <option value="<?php echo htmlspecialchars(trim($prov)); ?>" 
                                <?php echo $filter_province === trim($prov) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(trim($prov)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Call Type</label>
                    <select name="call_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($call_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                <?php echo $filter_call_type === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="critical" <?php echo $filter_priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="open" <?php echo $filter_status === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="assigned" <?php echo $filter_status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $filter_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="regional_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Calls Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Maintenance Calls
                <span class="badge bg-primary"><?php echo count($calls); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($calls)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No maintenance calls found for your region with the selected filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Call ID</th>
                                <th>Province</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Assigned To</th>
                                <th>Response Time</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calls as $call): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($call['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($call['province'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($call['department_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars(ucfirst($call['call_type'] ?? 'general')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($call['priority_name']): ?>
                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($call['priority_color'] ?? '#6c757d'); ?>">
                                                <?php echo htmlspecialchars($call['priority_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'open' => 'danger',
                                            'assigned' => 'warning',
                                            'in_progress' => 'info',
                                            'resolved' => 'success',
                                            'closed' => 'secondary'
                                        ];
                                        $status_color = $status_colors[$call['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $call['status']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($call['reporter_first_name']): ?>
                                            <?php echo htmlspecialchars($call['reporter_first_name'] . ' ' . $call['reporter_last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($call['assignee_first_name']): ?>
                                            <?php echo htmlspecialchars($call['assignee_first_name'] . ' ' . $call['assignee_last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($call['response_time_minutes']): ?>
                                            <?php
                                            $minutes = $call['response_time_minutes'];
                                            $badge_class = 'success';
                                            if ($minutes > 120) $badge_class = 'danger';
                                            elseif ($minutes > 60) $badge_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo round($minutes); ?> min
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('Y-m-d H:i', strtotime($call['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <a href="maintenance_calls/view.php?id=<?php echo $call['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
