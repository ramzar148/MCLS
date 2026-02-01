<?php
$page_title = 'View Department';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only managers and admins can view departments
if (!$session->hasRole('manager')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page'
    ];
    header('Location: ../dashboard.php');
    exit;
}

$current_user = $session->getCurrentUser();
$dept_id = (int)($_GET['id'] ?? 0);

if (!$dept_id) {
    header('Location: index.php');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get department details with manager info
    $stmt = $pdo->prepare("
        SELECT d.*, 
               CONCAT(u.first_name, ' ', u.last_name) as manager_name,
               u.email as manager_email
        FROM departments d
        LEFT JOIN users u ON d.manager_id = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$dept_id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Department not found'
        ];
        header('Location: index.php');
        exit;
    }
    
    // Get maintenance calls for this department
    $stmt = $pdo->prepare("
        SELECT mc.*, 
               p.name as priority_name,
               p.color_code as priority_color,
               CONCAT(u.first_name, ' ', u.last_name) as reported_by_name,
               DATEDIFF(NOW(), mc.reported_date) as days_open
        FROM maintenance_calls mc
        LEFT JOIN priority_levels p ON mc.priority_id = p.id
        LEFT JOIN users u ON mc.reported_by = u.id
        WHERE mc.department_id = ?
        ORDER BY mc.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$dept_id]);
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_calls,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_calls,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_calls,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_calls,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_calls
        FROM maintenance_calls
        WHERE department_id = ?
    ");
    $stmt->execute([$dept_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<style>
.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid #1e6b3e;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    font-size: 2rem;
    color: #1e6b3e;
    margin: 0 0 0.5rem 0;
}

.stat-card label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 500;
}

.info-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-section h4 {
    color: #1e6b3e;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.25rem;
}

.info-item .value {
    color: #212529;
}

.calls-table {
    font-size: 0.9rem;
}

.calls-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #1e6b3e;
    color: #495057;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
}

.calls-table td {
    vertical-align: middle;
}
</style>

<div class="page-header">
    <div>
        <h1>🏢 <?php echo htmlspecialchars($department['name']); ?></h1>
        <p>Department Details</p>
    </div>
    <div class="page-actions">
        <a href="index.php" class="btn btn-secondary">← Back to Departments</a>
        <?php if ($session->hasRole('admin')): ?>
            <a href="edit.php?id=<?php echo $dept_id; ?>" class="btn btn-primary">✏️ Edit Department</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Department Information -->
<div class="info-section">
    <h4>Department Information</h4>
    
    <div class="row">
        <div class="col-md-6">
            <div class="info-item">
                <label>Department Name</label>
                <div class="value"><?php echo htmlspecialchars($department['name']); ?></div>
            </div>
            
            <?php if ($department['description']): ?>
            <div class="info-item">
                <label>Description</label>
                <div class="value"><?php echo nl2br(htmlspecialchars($department['description'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <?php if ($department['manager_name']): ?>
            <div class="info-item">
                <label>Department Manager</label>
                <div class="value">
                    <?php echo htmlspecialchars($department['manager_name']); ?>
                    <?php if ($department['manager_email']): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($department['manager_email']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <label>Status</label>
                <div class="value">
                    <span class="badge badge-<?php echo $department['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($department['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <label>Created</label>
                <div class="value"><?php echo date('M d, Y', strtotime($department['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<h3>📊 Maintenance Call Statistics</h3>
<div class="row">
    <div class="col-md-2">
        <div class="stat-card">
            <h3><?php echo $stats['total_calls']; ?></h3>
            <label>Total Calls</label>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color: #dc3545;">
            <h3 style="color: #dc3545;"><?php echo $stats['open_calls']; ?></h3>
            <label>Open</label>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color: #ffc107;">
            <h3 style="color: #ffc107;"><?php echo $stats['assigned_calls']; ?></h3>
            <label>Assigned</label>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color: #17a2b8;">
            <h3 style="color: #17a2b8;"><?php echo $stats['in_progress_calls']; ?></h3>
            <label>In Progress</label>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color: #28a745;">
            <h3 style="color: #28a745;"><?php echo $stats['resolved_calls']; ?></h3>
            <label>Resolved</label>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color: #6c757d;">
            <h3 style="color: #6c757d;"><?php echo $stats['closed_calls']; ?></h3>
            <label>Closed</label>
        </div>
    </div>
</div>

<!-- Recent Maintenance Calls -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>🔧 Recent Maintenance Calls</h3>
    </div>
    <div class="card-body">
        <?php if (empty($calls)): ?>
            <p class="text-center text-muted">No maintenance calls for this department yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover calls-table">
                    <thead>
                        <tr>
                            <th>Call Number</th>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported By</th>
                            <th>Date</th>
                            <th>Days Open</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calls as $call): ?>
                            <tr>
                                <td>
                                    <a href="../maintenance_calls/view.php?id=<?php echo $call['id']; ?>">
                                        <?php echo htmlspecialchars($call['call_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($call['title']); ?></td>
                                <td>
                                    <span class="badge badge-priority" style="background-color: <?php echo htmlspecialchars($call['priority_color'] ?? '#6c757d'); ?>">
                                        <?php echo htmlspecialchars($call['priority_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-status-<?php echo $call['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $call['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($call['reported_by_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($call['reported_date'])); ?></td>
                                <td><?php echo $call['days_open']; ?> days</td>
                                <td>
                                    <a href="../maintenance_calls/view.php?id=<?php echo $call['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($calls) >= 20): ?>
                <div class="text-center" style="margin-top: 1rem;">
                    <a href="../maintenance_calls/index.php?department_id=<?php echo $dept_id; ?>" class="btn btn-outline-secondary">
                        View All Calls for This Department
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
