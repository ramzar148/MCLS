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
            // Add new coordinator
            $stmt = $pdo->prepare("
                INSERT INTO regional_coordinators (name, email, phone, region, provinces, position, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['region'],
                $_POST['provinces'],
                $_POST['position']
            ]);
            $success_message = "Coordinator added successfully!";
            
        } elseif ($action === 'edit') {
            // Update coordinator
            $stmt = $pdo->prepare("
                UPDATE regional_coordinators 
                SET name = ?, email = ?, phone = ?, region = ?, provinces = ?, position = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['region'],
                $_POST['provinces'],
                $_POST['position'],
                $_POST['id']
            ]);
            $success_message = "Coordinator updated successfully!";
            
        } elseif ($action === 'delete') {
            // Delete coordinator
            $stmt = $pdo->prepare("DELETE FROM regional_coordinators WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success_message = "Coordinator deleted successfully!";
            
        } elseif ($action === 'toggle_status') {
            // Toggle active/inactive
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
        ORDER BY region, position
    ");
    $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get notification statistics
    $stmt = $pdo->query("
        SELECT 
            recipient_type,
            notification_type,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM notification_log
        WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY recipient_type, notification_type
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent notifications
    $stmt = $pdo->query("
        SELECT nl.*, mc.call_number
        FROM notification_log nl
        LEFT JOIN maintenance_calls mc ON nl.maintenance_call_id = mc.id
        ORDER BY nl.sent_at DESC
        LIMIT 20
    ");
    $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<style>
.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #1e6b3e;
    margin-bottom: 20px;
}

.stat-card h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #1e6b3e;
}

.stat-card label {
    display: block;
    margin-top: 8px;
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
}

.coordinator-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.coordinator-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.coordinator-card.coastal {
    border-left: 4px solid #0dcaf0;
}

.coordinator-card.inland {
    border-left: 4px solid #fd7e14;
}

.coordinator-card h5 {
    color: #1e6b3e;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.coordinator-card .badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

.coordinator-card .btn-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.section-header {
    background: linear-gradient(135deg, #1e6b3e 0%, #2d8659 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.section-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.section-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 2px solid #dee2e6;
}

.table-header h4 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e6b3e;
}

.table thead th {
    background-color: #1e6b3e;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    border: none;
}

.table tbody tr {
    transition: background-color 0.2s;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.notification-log {
    font-size: 0.9rem;
}

.notification-log .status-sent {
    color: #28a745;
}

.notification-log .status-failed {
    color: #dc3545;
}

.page-header {
    margin-bottom: 2rem;
}

.page-actions {
    display: flex;
    gap: 0.5rem;
}
</style>

<div class="container-fluid mt-4">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="fas fa-users-cog text-primary"></i> Regional Coordinators</h1>
            <p class="text-muted mb-0">Manage DFFE Regional Notification System</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoordinatorModal">
                <i class="fas fa-plus"></i> Add Coordinator
            </button>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

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

    <!-- Notification Statistics -->
    <div class="row mb-4">
        <?php
        $total_sent = 0;
        $total_failed = 0;
        foreach ($stats as $stat) {
            $total_sent += $stat['sent'];
            $total_failed += $stat['failed'];
        }
        ?>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo $total_sent + $total_failed; ?></h3>
                <label>Total Notifications (30 Days)</label>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h3 style="color: #28a745;"><?php echo $total_sent; ?></h3>
                <label>Successfully Sent</label>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h3 style="color: #dc3545;"><?php echo $total_failed; ?></h3>
                <label>Failed</label>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h3 style="color: #0dcaf0;"><?php echo count($coordinators); ?></h3>
                <label>Active Coordinators</label>
            </div>
        </div>
    </div>

    <!-- Regional Coordinators -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="section-header" style="background: linear-gradient(135deg, #0dcaf0 0%, #17a2b8 100%);">
                <h3><i class="fas fa-water"></i> Coastal Region</h3>
                <p>Western Cape, Eastern Cape, KwaZulu-Natal</p>
            </div>
        
        <?php foreach ($coordinators as $coord): ?>
            <?php if ($coord['region'] === 'coastal'): ?>
                <div class="card coordinator-card coastal mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($coord['name']); ?></h5>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($coord['position']); ?></p>
                            </div>
                            <span class="badge bg-<?php echo $coord['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($coord['status']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <p class="mb-1">
                                <i class="fas fa-envelope text-muted"></i>
                                <a href="mailto:<?php echo htmlspecialchars($coord['email']); ?>" class="ms-2">
                                    <?php echo htmlspecialchars($coord['email']); ?>
                                </a>
                            </p>
                        </p>
                        <?php if ($coord['phone']): ?>
                            <p class="mb-1">📞 <?php echo htmlspecialchars($coord['phone']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <small class="text-muted">
                                Provinces: <?php echo htmlspecialchars($coord['provinces']); ?>
                            </small>
                        </p>
                        <div class="mt-2">
                            <span class="badge badge-<?php echo $coord['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($coord['status']); ?>
                            </span>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-primary" onclick="editCoordinator(<?php echo htmlspecialchars(json_encode($coord)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status for this coordinator?');">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $coord['status'] === 'active' ? 'warning' : 'success'; ?>">
                                    <i class="fas fa-toggle-<?php echo $coord['status'] === 'active' ? 'off' : 'on'; ?>"></i>
                                    <?php echo $coord['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this coordinator?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="col-md-6">
        <h3>🏔️ Inland Region Coordinators</h3>
        <p class="text-muted">Gauteng, Limpopo, Mpumalanga, North West, Free State, Northern Cape</p>
        
        <?php foreach ($coordinators as $coord): ?>
            <?php if ($coord['region'] === 'inland'): ?>
                <div class="card coordinator-card inland">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($coord['name']); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($coord['position']); ?></p>
                        <p class="mb-1">
                            📧 <a href="mailto:<?php echo htmlspecialchars($coord['email']); ?>">
                                <?php echo htmlspecialchars($coord['email']); ?>
                            </a>
                        </p>
                        <?php if ($coord['phone']): ?>
                            <p class="mb-1">📞 <?php echo htmlspecialchars($coord['phone']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <small class="text-muted">
                                Provinces: <?php echo htmlspecialchars($coord['provinces']); ?>
                            </small>
                        </p>
                        <div class="mt-2">
                            <span class="badge badge-<?php echo $coord['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($coord['status']); ?>
                            </span>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-primary" onclick="editCoordinator(<?php echo htmlspecialchars(json_encode($coord)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status for this coordinator?');">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $coord['status'] === 'active' ? 'warning' : 'success'; ?>">
                                    <i class="fas fa-toggle-<?php echo $coord['status'] === 'active' ? 'off' : 'on'; ?>"></i>
                                    <?php echo $coord['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this coordinator?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    </div>

    <!-- Recent Notifications -->
    <div class="table-container">
        <div class="table-header">
            <h4><i class="fas fa-bell"></i> Recent Notifications</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
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
                <tbody class="notification-log">
                    <?php if (empty($recent_notifications)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No notifications sent yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_notifications as $notification): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($notification['sent_at'])); ?></td>
                                <td>
                                    <a href="../maintenance_calls/view.php?id=<?php echo $notification['maintenance_call_id']; ?>">
                                        <?php echo htmlspecialchars($notification['call_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo str_replace('_', ' ', ucwords($notification['notification_type'], '_')); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($notification['recipient_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($notification['recipient_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($notification['subject']); ?></td>
                                <td>
                                    <span class="status-<?php echo $notification['status']; ?>">
                                        <?php echo ucfirst($notification['status']); ?>
                                        <?php if ($notification['status'] === 'sent'): ?>✓<?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                        <input type="text" name="phone" class="form-control">
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
                        <input type="text" name="provinces" class="form-control" placeholder="e.g., Western Cape, Eastern Cape" required>
                        <small class="form-text text-muted">Comma-separated list of provinces</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position *</label>
                        <input type="text" name="position" class="form-control" placeholder="e.g., Regional Coordinator" required>
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
                        <small class="form-text text-muted">Comma-separated list of provinces</small>
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
