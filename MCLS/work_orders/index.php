<?php
$page_title = 'Work Orders';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

// Get work orders
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT wo.*, mc.title as call_title, mc.call_number, u.first_name, u.last_name
        FROM work_orders wo
        LEFT JOIN maintenance_calls mc ON wo.maintenance_call_id = mc.id
        LEFT JOIN users u ON wo.assigned_to = u.id
        ORDER BY wo.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $work_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $work_orders = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>🔧 Work Orders</h1>
        <p>Manage and track maintenance work orders</p>
        
        <div class="page-actions">
            <a href="create.php" class="btn btn-primary">+ Create Work Order</a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Work Orders</h3>
        </div>
        <div class="card-body">
            <?php if (empty($work_orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔧</div>
                    <h3>No Work Orders Found</h3>
                    <p>Start by creating your first work order.</p>
                    <a href="create.php" class="btn btn-primary">Create Work Order</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Work Order #</th>
                                <th>Maintenance Call</th>
                                <th>Description</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($work_orders as $wo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($wo['work_order_number']); ?></td>
                                    <td>
                                        <a href="../maintenance_calls/view.php?id=<?php echo $wo['maintenance_call_id']; ?>">
                                            <?php echo htmlspecialchars($wo['call_number'] . ' - ' . $wo['call_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($wo['description'], 0, 80) . (strlen($wo['description']) > 80 ? '...' : '')); ?></td>
                                    <td><?php echo htmlspecialchars(($wo['first_name'] ?? '') . ' ' . ($wo['last_name'] ?? '') ?: 'Unassigned'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $wo['status'] == 'completed' ? 'success' : ($wo['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $wo['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($wo['created_at'])); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="edit.php?id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
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

<style>
.page-header {
    margin-bottom: 24px;
}

.page-actions {
    margin-top: 16px;
}

.card {
    margin-bottom: 20px;
}

.empty-state {
    padding: 48px 20px;
}
</style>

<?php require_once '../includes/footer.php'; ?>