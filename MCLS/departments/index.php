<?php
$page_title = 'Department Management';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Check if user has permission to manage departments
if (!$session->hasRole('manager')) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied. Manager privileges required.');
}

$current_user = $session->getCurrentUser();

// Get departments
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               u.first_name as manager_first_name, 
               u.last_name as manager_last_name,
               COUNT(DISTINCT e.id) as equipment_count,
               COUNT(DISTINCT du.id) as user_count
        FROM departments d
        LEFT JOIN users u ON d.manager_id = u.id
        LEFT JOIN equipment e ON d.id = e.department_id
        LEFT JOIN users du ON d.id = du.department_id
        GROUP BY d.id
        ORDER BY d.name
    ");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departments = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>🏢 Department Management</h1>
        <p>Manage organizational departments and structure</p>
        
        <?php if ($session->hasRole('admin')): ?>
        <div class="page-actions">
            <a href="create.php" class="btn btn-primary">+ Create Department</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>DFFE Departments</h3>
        </div>
        <div class="card-body">
            <?php if (empty($departments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏢</div>
                    <h3>No Departments Found</h3>
                    <p>Start by creating organizational departments.</p>
                    <?php if ($session->hasRole('admin')): ?>
                        <a href="create.php" class="btn btn-primary">Create Department</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($departments as $dept): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card department-card">
                                <div class="card-header">
                                    <h4><?php echo htmlspecialchars($dept['name']); ?></h4>
                                    <span class="badge badge-<?php echo $dept['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($dept['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p><strong>Code:</strong> <?php echo htmlspecialchars($dept['code']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($dept['location'] ?? 'Not specified'); ?></p>
                                    <p><strong>Manager:</strong> 
                                        <?php 
                                        if ($dept['manager_first_name']) {
                                            echo htmlspecialchars($dept['manager_first_name'] . ' ' . $dept['manager_last_name']);
                                        } else {
                                            echo 'Not assigned';
                                        }
                                        ?>
                                    </p>
                                    
                                    <div class="department-stats">
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $dept['user_count']; ?></span>
                                            <span class="stat-label">Users</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $dept['equipment_count']; ?></span>
                                            <span class="stat-label">Equipment</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="view.php?id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
                                        <?php if ($session->hasRole('admin')): ?>
                                            <a href="edit.php?id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Department Overview Stats -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">🏢</div>
                <div class="stats-content">
                    <h3><?php echo count($departments); ?></h3>
                    <p>Total Departments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">✅</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($departments, fn($d) => $d['status'] == 'active')); ?></h3>
                    <p>Active Departments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">👥</div>
                <div class="stats-content">
                    <h3><?php echo array_sum(array_column($departments, 'user_count')); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">⚙️</div>
                <div class="stats-content">
                    <h3><?php echo array_sum(array_column($departments, 'equipment_count')); ?></h3>
                    <p>Total Equipment</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.department-card {
    border: 1px solid #e9ecef;
    transition: transform 0.2s ease-in-out;
}

.department-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.department-stats {
    display: flex;
    justify-content: space-around;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>

<?php require_once '../includes/footer.php'; ?>