<?php
$page_title = 'Equipment Management';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

// Get equipment
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT e.*, d.name as department_name
        FROM equipment e
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY e.name
        LIMIT 50
    ");
    $stmt->execute();
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $equipment = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>⚙️ Equipment Management</h1>
        <p>Manage organizational equipment and assets</p>
        
        <div class="page-actions">
            <a href="create.php" class="btn btn-primary">+ Add Equipment</a>
            <a href="categories.php" class="btn btn-secondary">Categories</a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Equipment Inventory</h3>
        </div>
        <div class="card-body">
            <?php if (empty($equipment)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">⚙️</div>
                    <h3>No Equipment Found</h3>
                    <p>Start by adding equipment to the inventory.</p>
                    <a href="create.php" class="btn btn-primary">Add Equipment</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['asset_tag']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($item['department_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $item['status'] == 'active' ? 'success' : ($item['status'] == 'maintenance' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
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

<?php require_once '../includes/footer.php'; ?>