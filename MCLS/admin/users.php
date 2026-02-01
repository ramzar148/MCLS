<?php
$page_title = 'User Management';
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

// Get users
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.*, d.name as department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>👥 User Management</h1>
        <p>Manage user accounts and access permissions</p>
        
        <div class="page-actions">
            <a href="create_user.php" class="btn btn-primary">+ Add User</a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>System Users</h3>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>No Users Found</h3>
                    <p>Start by adding users to the system.</p>
                    <a href="create_user.php" class="btn btn-primary">Add User</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['ad_username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'manager' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <?php if ($user['id'] != $current_user['id']): ?>
                                            <a href="disable_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Disable</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">👥</div>
                <div class="stats-content">
                    <h3><?php echo count($users); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">✅</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($users, fn($u) => $u['status'] == 'active')); ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">👑</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($users, fn($u) => $u['role'] == 'admin')); ?></h3>
                    <p>Administrators</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">📞</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($users, fn($u) => $u['last_login'])); ?></h3>
                    <p>Users Logged In</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>