<?php
$page_title = 'System Administration';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Check if user has admin privileges
if (!$session->hasRole('admin')) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied. Administrator privileges required.');
}

$current_user = $session->getCurrentUser();

// Get system statistics
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get counts
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM maintenance_calls WHERE status IN ('open', 'assigned', 'in_progress')");
    $stats['open_calls'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'active'");
    $stats['active_equipment'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments WHERE status = 'active'");
    $stats['active_departments'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $stats = ['active_users' => 0, 'open_calls' => 0, 'active_equipment' => 0, 'active_departments' => 0];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>⚙️ System Administration</h1>
        <p>Manage system settings, users, and configurations</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- System Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">👥</div>
                <div class="stats-content">
                    <h3><?php echo $stats['active_users']; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">📞</div>
                <div class="stats-content">
                    <h3><?php echo $stats['open_calls']; ?></h3>
                    <p>Open Calls</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">⚙️</div>
                <div class="stats-content">
                    <h3><?php echo $stats['active_equipment']; ?></h3>
                    <p>Active Equipment</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">🏢</div>
                <div class="stats-content">
                    <h3><?php echo $stats['active_departments']; ?></h3>
                    <p>Departments</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Administration Sections -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>👥 User Management</h3>
                </div>
                <div class="card-body">
                    <p>Manage user accounts, roles, and permissions</p>
                    <ul>
                        <li><a href="users.php">User Accounts</a></li>
                        <li><a href="roles.php">Role Management</a></li>
                        <li><a href="permissions.php">Permission Settings</a></li>
                        <li><a href="active_sessions.php">Active Sessions</a></li>
                    </ul>
                    <div class="mt-3">
                        <a href="users.php" class="btn btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>🔒 Security & Audit</h3>
                </div>
                <div class="card-body">
                    <p>Monitor system security and audit trails</p>
                    <ul>
                        <li><a href="audit.php">Audit Log</a></li>
                        <li><a href="login_attempts.php">Login Attempts</a></li>
                        <li><a href="security_settings.php">Security Settings</a></li>
                        <li><a href="system_logs.php">System Logs</a></li>
                    </ul>
                    <div class="mt-3">
                        <a href="audit.php" class="btn btn-primary">View Audit Log</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>⚙️ System Configuration</h3>
                </div>
                <div class="card-body">
                    <p>Configure system settings and parameters</p>
                    <ul>
                        <li><a href="settings.php">System Settings</a></li>
                        <li><a href="email_config.php">Email Configuration</a></li>
                        <li><a href="notification_settings.php">Notifications</a></li>
                        <li><a href="backup_settings.php">Backup Settings</a></li>
                    </ul>
                    <div class="mt-3">
                        <a href="settings.php" class="btn btn-primary">System Settings</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>🔧 System Maintenance</h3>
                </div>
                <div class="card-body">
                    <p>System maintenance and monitoring tools</p>
                    <ul>
                        <li><a href="database_status.php">Database Status</a></li>
                        <li><a href="system_health.php">System Health</a></li>
                        <li><a href="maintenance_mode.php">Maintenance Mode</a></li>
                        <li><a href="cache_management.php">Cache Management</a></li>
                    </ul>
                    <div class="mt-3">
                        <a href="system_health.php" class="btn btn-primary">System Health</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DFFE Specific Administration -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>🌿 DFFE Specific Configuration</h3>
        </div>
        <div class="card-body">
            <p>Department of Forestry, Fisheries and the Environment specific settings</p>
            <div class="row">
                <div class="col-md-4">
                    <h5>🌳 Forestry Management</h5>
                    <ul>
                        <li><a href="forestry_config.php">Forestry Settings</a></li>
                        <li><a href="conservation_areas.php">Conservation Areas</a></li>
                        <li><a href="fire_management.php">Fire Management</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>🐟 Fisheries Management</h5>
                    <ul>
                        <li><a href="fisheries_config.php">Fisheries Settings</a></li>
                        <li><a href="marine_protected_areas.php">Marine Protected Areas</a></li>
                        <li><a href="vessel_registry.php">Vessel Registry</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>🌍 Environmental Settings</h5>
                    <ul>
                        <li><a href="environmental_config.php">Environmental Settings</a></li>
                        <li><a href="compliance_standards.php">Compliance Standards</a></li>
                        <li><a href="monitoring_protocols.php">Monitoring Protocols</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>