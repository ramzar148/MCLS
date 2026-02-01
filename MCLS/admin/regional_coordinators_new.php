<?php
$page_title = 'Regional Coordinators';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only admins and managers can access
if (!$session->hasRole('manager')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page'
    ];
    header('Location: ../dashboard.php');
    exit;
}

$current_user = $session->getCurrentUser();

// Handle form submissions
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO regional_coordinators (name, email, phone, region, provinces, position, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'] ?? null,
                $_POST['region'],
                $_POST['provinces'],
                $_POST['position']
            ]);
            $success_message = "Coordinator added successfully!";
            
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE regional_coordinators 
                SET name = ?, email = ?, phone = ?, region = ?, provinces = ?, position = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'] ?? null,
                $_POST['region'],
                $_POST['provinces'],
                $_POST['position'],
                $_POST['id']
            ]);
            $success_message = "Coordinator updated successfully!";
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM regional_coordinators WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success_message = "Coordinator deleted successfully!";
            
        } elseif ($action === 'toggle_status') {
            $stmt = $pdo->prepare("
                UPDATE regional_coordinators 
                SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_POST['id']]);
            $success_message = "Coordinator status updated!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get all regional coordinators
    $stmt = $pdo->query("
        SELECT * FROM regional_coordinators 
        ORDER BY region, name
    ");
    $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get notification statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM notification_log
        WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent notifications
    $stmt = $pdo->query("
        SELECT nl.*, mc.call_number
        FROM notification_log nl
        LEFT JOIN maintenance_calls mc ON nl.maintenance_call_id = mc.id
        ORDER BY nl.sent_at DESC
        LIMIT 10
    ");
    $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Regional Coordinators</h1>
            <p class="text-muted small mb-0">Manage DFFE regional notification system</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoordinatorModal">
                <i class="fas fa-plus"></i> Add Coordinator
            </button>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Coordinators</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($coordinators); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Notifications (30 Days)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Successfully Sent</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['sent'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['failed'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coordinators Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Regional Coordinators</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Region</th>
                            <th>Provinces</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coordinators)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No coordinators found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coordinators as $coord): ?>
                                <tr>
                                    <td class="font-weight-bold"><?php echo htmlspecialchars($coord['name']); ?></td>
                                    <td>
                                        <?php if ($coord['region'] === 'coastal'): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-water"></i> Coastal
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-mountain"></i> Inland
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($coord['provinces']); ?></small></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($coord['email']); ?>">
                                            <?php echo htmlspecialchars($coord['email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($coord['phone'] ?? '-'); ?></td>
                                    <td><small><?php echo htmlspecialchars($coord['position']); ?></small></td>
                                    <td>
                                        <?php if ($coord['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editCoordinator(<?php echo htmlspecialchars(json_encode($coord)); ?>)"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('Toggle status?');">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm btn-<?php echo $coord['status'] === 'active' ? 'warning' : 'success'; ?>"
                                                    title="<?php echo $coord['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this coordinator?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Notifications Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Call Number</th>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_notifications)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No notifications sent yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notification): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($notification['sent_at'])); ?></td>
                                    <td>
                                        <?php if ($notification['call_number']): ?>
                                            <a href="../maintenance_calls/view.php?id=<?php echo $notification['maintenance_call_id']; ?>">
                                                <?php echo htmlspecialchars($notification['call_number']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($notification['notification_type']); ?></small></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($notification['recipient_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($notification['recipient_email']); ?></small>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($notification['subject']); ?></small></td>
                                    <td>
                                        <?php if ($notification['status'] === 'sent'): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Sent
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times"></i> Failed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Coordinator Modal -->
<div class="modal fade" id="addCoordinatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Regional Coordinator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Region *</label>
                        <select name="region" class="form-select" required>
                            <option value="">Select Region</option>
                            <option value="coastal">Coastal</option>
                            <option value="inland">Inland</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Provinces *</label>
                        <input type="text" name="provinces" class="form-control" 
                               placeholder="e.g., Western Cape, Eastern Cape" required>
                        <small class="form-text text-muted">Comma-separated list</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position *</label>
                        <input type="text" name="position" class="form-control" 
                               placeholder="e.g., Regional Coordinator" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Coordinator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coordinator Modal -->
<div class="modal fade" id="editCoordinatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Regional Coordinator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Region *</label>
                        <select name="region" id="edit_region" class="form-select" required>
                            <option value="coastal">Coastal</option>
                            <option value="inland">Inland</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Provinces *</label>
                        <input type="text" name="provinces" id="edit_provinces" class="form-control" required>
                        <small class="form-text text-muted">Comma-separated list</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position *</label>
                        <input type="text" name="position" id="edit_position" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Coordinator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #4e73df!important; }
.border-left-success { border-left: 4px solid #1cc88a!important; }
.border-left-danger { border-left: 4px solid #e74a3b!important; }
.text-primary { color: #4e73df!important; }
.text-success { color: #1cc88a!important; }
.text-danger { color: #e74a3b!important; }
.text-gray-800 { color: #5a5c69!important; }
.text-gray-300 { color: #dddfeb!important; }
</style>

<script>
function editCoordinator(coord) {
    document.getElementById('edit_id').value = coord.id;
    document.getElementById('edit_name').value = coord.name;
    document.getElementById('edit_email').value = coord.email;
    document.getElementById('edit_phone').value = coord.phone || '';
    document.getElementById('edit_region').value = coord.region;
    document.getElementById('edit_provinces').value = coord.provinces;
    document.getElementById('edit_position').value = coord.position;
    
    var modal = new bootstrap.Modal(document.getElementById('editCoordinatorModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
