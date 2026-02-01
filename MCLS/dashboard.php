<?php
$page_title = 'Dashboard';
require_once 'bootstrap.php';
require_once 'config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

// Redirect coordinators to regional dashboard
if ($current_user['role'] === 'coordinator') {
    header('Location: regional_dashboard.php');
    exit;
}

// Helper function to check exact role (not hierarchical)
function isExactRole($session, $role) {
    return $session->isRole($role);
}

// Get dashboard statistics based on user role
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize all stats with default values
    $stats = [
        'total_calls' => 0, 
        'open_calls' => 0, 
        'critical_calls' => 0,
        'my_calls' => 0, 
        'reported_calls' => 0,
        'active_users' => 0,
        'active_equipment' => 0,
        'department_calls' => 0
    ];
    
    // Common statistics for all users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM maintenance_calls");
    $stmt->execute();
    $stats['total_calls'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as open FROM maintenance_calls WHERE status IN ('open', 'assigned', 'in_progress')");
    $stmt->execute();
    $stats['open_calls'] = $stmt->fetch()['open'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as critical FROM maintenance_calls mc JOIN priority_levels pl ON mc.priority_id = pl.id WHERE pl.name = 'Critical'");
    $stmt->execute();
    $stats['critical_calls'] = $stmt->fetch()['critical'];
    
    // Role-specific statistics
    if ($session->isRole('admin')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as users FROM users WHERE status = 'active'");
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch()['users'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as equipment FROM equipment WHERE status = 'active'");
        $stmt->execute();
        $stats['active_equipment'] = $stmt->fetch()['equipment'];
    }
    
    if ($session->isRole('manager')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as dept_calls FROM maintenance_calls mc JOIN users u ON mc.reported_by = u.id WHERE u.department_id = ?");
        $stmt->execute([$current_user['department_id']]);
        $stats['department_calls'] = $stmt->fetch()['dept_calls'];
    }
    
    // Personal statistics for technicians and users
    $stmt = $pdo->prepare("SELECT COUNT(*) as my_calls FROM maintenance_calls WHERE assigned_to = ? AND status IN ('assigned', 'in_progress')");
    $stmt->execute([$current_user['id']]);
    $stats['my_calls'] = $stmt->fetch()['my_calls'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as reported FROM maintenance_calls WHERE reported_by = ?");
    $stmt->execute([$current_user['id']]);
    $stats['reported_calls'] = $stmt->fetch()['reported'];
    
    // Recent activity
    $limit = $session->isRole('admin') ? 10 : 5;
    $stmt = $pdo->prepare("
        SELECT mc.*, pl.name as priority_name, pl.color_code, pl.name as priority,
               u1.first_name as reporter_first, u1.last_name as reporter_last,
               u2.first_name as assignee_first, u2.last_name as assignee_last,
               e.name as equipment_name,
               DATEDIFF(NOW(), mc.created_at) as days_open
        FROM maintenance_calls mc
        LEFT JOIN priority_levels pl ON mc.priority_id = pl.id
        LEFT JOIN users u1 ON mc.reported_by = u1.id
        LEFT JOIN users u2 ON mc.assigned_to = u2.id
        LEFT JOIN equipment e ON mc.equipment_id = e.id
        ORDER BY mc.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = [
        'total_calls' => 0, 
        'open_calls' => 0, 
        'critical_calls' => 0,
        'my_calls' => 0, 
        'reported_calls' => 0,
        'active_users' => 0,
        'active_equipment' => 0,
        'department_calls' => 0
    ];
    $recent_calls = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once 'includes/header.php';
?>
<div class="main-content">
    <div class="page-header">
        <h1>🏠 Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</p>
        
        <?php if (!empty($current_user['department_name'])): ?>
            <p class="text-muted"><?php echo htmlspecialchars($current_user['department_name']); ?> • <?php echo ucfirst($current_user['role']); ?></p>
        <?php else: ?>
            <p class="text-muted"><?php echo ucfirst($current_user['role']); ?></p>
        <?php endif; ?>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Role-specific dashboard content -->
    <?php if (isExactRole($session, 'admin')): ?>
        <!-- Admin Dashboard -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">📞</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['total_calls']; ?></h3>
                        <p>Total Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">🔓</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['open_calls']; ?></h3>
                        <p>Open Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">👥</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['active_users'] ?? 0; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">⚙️</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['active_equipment'] ?? 0; ?></h3>
                        <p>Equipment</p>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif (isExactRole($session, 'manager')): ?>
        <!-- Manager Dashboard -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">🏢</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['department_calls'] ?? 0; ?></h3>
                        <p>Department Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">🔓</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['open_calls']; ?></h3>
                        <p>Open Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">📋</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['my_calls']; ?></h3>
                        <p>Assigned to Me</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">📝</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['reported_calls']; ?></h3>
                        <p>I Reported</p>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif (isExactRole($session, 'technician')): ?>
        <!-- Technician Dashboard -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">🔧</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['my_calls']; ?></h3>
                        <p>My Active Tasks</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">🔓</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['open_calls']; ?></h3>
                        <p>Open Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">📝</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['reported_calls']; ?></h3>
                        <p>I Reported</p>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- User Dashboard -->
        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">📝</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['reported_calls']; ?></h3>
                        <p>My Reported Calls</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">🔓</div>
                    <div class="stats-content">
                        <h3><?php echo $stats['open_calls']; ?></h3>
                        <p>Open Calls</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions based on role -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="maintenance_calls/create.php" class="btn btn-primary">
                            📞 Report Issue
                        </a>
                        
                        <?php if ($session->hasRole('technician')): ?>
                            <a href="maintenance_calls/index.php?filter=assigned_to_me" class="btn btn-warning">
                                🔧 My Tasks
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($session->hasRole('manager')): ?>
                            <a href="work_orders/index.php" class="btn btn-info">
                                📋 Work Orders
                            </a>
                            <a href="reports/index.php" class="btn btn-secondary">
                                📊 Reports
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($session->hasRole('admin')): ?>
                            <a href="admin/index.php" class="btn btn-danger">
                                ⚙️ Administration
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Maintenance Calls</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_calls)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📞</div>
                            <h4>No Recent Activity</h4>
                            <p>Recent maintenance calls will appear here.</p>
                            <a href="maintenance_calls/create.php" class="btn btn-primary">Report First Issue</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Call #</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Reported By</th>
                                        <?php if ($session->hasRole('technician')): ?>
                                            <th>Assigned To</th>
                                        <?php endif; ?>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_calls as $call): ?>
                                        <tr>
                                            <td>
                                                <a href="maintenance_calls/view.php?id=<?php echo $call['id']; ?>">
                                                    <?php echo htmlspecialchars($call['call_number']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($call['title']); ?></td>
                                            <td>
                                                <span class="priority-badge" style="background-color: <?php echo $call['color_code'] ?? '#6c757d'; ?>">
                                                    <?php echo htmlspecialchars($call['priority_name'] ?? 'Normal'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $call['status'] == 'open' ? 'danger' : 
                                                        ($call['status'] == 'in_progress' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $call['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($call['reporter_first'] . ' ' . $call['reporter_last']); ?></td>
                                            <?php if ($session->hasRole('technician')): ?>
                                                <td><?php echo htmlspecialchars(($call['assignee_first'] ?? '') . ' ' . ($call['assignee_last'] ?? '') ?: 'Unassigned'); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo date('M j, Y', strtotime($call['created_at'])); ?></td>
                                            <td>
                                                <a href="maintenance_calls/view.php?id=<?php echo $call['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="maintenance_calls/index.php" class="btn btn-outline">View All Calls</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Grid System */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.col-md-3, .col-md-4, .col-md-6 {
    padding: 0 10px;
    margin-bottom: 15px;
    box-sizing: border-box;
}

.col-md-3 {
    flex: 0 0 25%;
    max-width: 25%;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

.col-md-12 {
    flex: 0 0 100%;
    max-width: 100%;
    padding: 0 10px;
}

/* Responsive breakpoints */
@media (max-width: 1024px) {
    .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 768px) {
    .col-md-3, .col-md-4, .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.priority-badge {
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: bold;
}

.stats-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
    text-align: center;
    transition: transform 0.2s ease-in-out;
    height: 100%;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stats-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.stats-content h3 {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
    margin: 0 0 5px 0;
}

.stats-content p {
    color: #6c757d;
    margin: 0;
    font-size: 0.85rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>

