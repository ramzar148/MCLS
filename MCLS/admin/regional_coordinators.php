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
        
        // Redirect to prevent form resubmission
        header('Location: regional_coordinators.php?success=' . urlencode($success_message));
        exit;
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
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

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid var(--primary-green);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-card.danger { border-left-color: var(--status-critical); }
.stat-card.success { border-left-color: var(--status-success); }

.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--medium-gray);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--charcoal);
}

.stat-icon {
    font-size: 1.5rem;
    opacity: 0.3;
    float: right;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    animation: fadeIn 0.2s;
}

.modal-container {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    z-index: 9999;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s;
}

.modal-container.active,
.modal-overlay.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to { 
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--light-gray);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--charcoal);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--medium-gray);
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.modal-close:hover {
    background: var(--off-white);
    color: var(--charcoal);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--light-gray);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--charcoal);
    font-size: 0.9rem;
}

.form-input,
.form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--light-gray);
    border-radius: 4px;
    font-size: 0.9rem;
    font-family: inherit;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(30, 107, 62, 0.1);
}

.form-help {
    font-size: 0.8rem;
    color: var(--medium-gray);
    margin-top: 4px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge.badge-coastal {
    background: var(--primary-green);
    color: var(--pure-white);
}

.badge.badge-inland {
    background: var(--charcoal);
    color: var(--pure-white);
}

.badge.badge-active {
    background: var(--success-green);
    color: var(--pure-white);
}

.badge.badge-inactive {
    background: var(--medium-gray);
    color: var(--pure-white);
}

.badge.badge-sent {
    background: var(--success-green);
    color: var(--pure-white);
}

.badge.badge-failed {
    background: var(--status-critical);
    color: var(--pure-white);
}
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Regional Coordinators</h1>
            <p class="text-muted small mb-0">Manage DFFE regional notification system</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="openAddModal()">
                <span>➕</span> Add Coordinator
            </button>
            <a href="../dashboard.php" class="btn btn-secondary">
                <span>⬅️</span> Back
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <span>✓</span> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <span>✗</span> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Coordinators</div>
            <div class="stat-value"><?php echo count($coordinators); ?></div>
            <div class="stat-icon">👥</div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Notifications (30 Days)</div>
            <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-icon">🔔</div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Successfully Sent</div>
            <div class="stat-value"><?php echo $stats['sent'] ?? 0; ?></div>
            <div class="stat-icon">✓</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Failed</div>
            <div class="stat-value"><?php echo $stats['failed'] ?? 0; ?></div>
            <div class="stat-icon">✗</div>
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
                                            <span class="badge badge-coastal">
                                                Coastal
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-inland">
                                                Inland
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
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick='editCoordinator(<?php echo json_encode($coord); ?>)'
                                                title="Edit">✏️</button>
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('Toggle status?');">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-<?php echo $coord['status'] === 'active' ? 'secondary' : 'success'; ?> btn-sm"
                                                    title="<?php echo $coord['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <?php echo $coord['status'] === 'active' ? '⏸️' : '▶️'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this coordinator?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $coord['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">🗑️</button>
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
                                            <span class="badge badge-sent">
                                                Sent
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-failed">
                                                Failed
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

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>

<!-- Add Coordinator Modal -->
<div class="modal-container" id="addModal">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
            <h5 class="modal-title">Add Regional Coordinator</h5>
            <button type="button" class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" placeholder="Optional">
            </div>
            <div class="form-group">
                <label class="form-label">Region *</label>
                <select name="region" class="form-select" required>
                    <option value="">Select Region</option>
                    <option value="coastal">Coastal</option>
                    <option value="inland">Inland</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Provinces *</label>
                <input type="text" name="provinces" class="form-input" 
                       placeholder="e.g., Western Cape, Eastern Cape" required>
                <small class="form-help">Comma-separated list</small>
            </div>
            <div class="form-group">
                <label class="form-label">Position *</label>
                <input type="text" name="position" class="form-input" 
                       placeholder="e.g., Regional Coordinator" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Coordinator</button>
        </div>
    </form>
</div>

<!-- Edit Coordinator Modal -->
<div class="modal-container" id="editModal">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
            <h5 class="modal-title">Edit Regional Coordinator</h5>
            <button type="button" class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="name" id="edit_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" id="edit_phone" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Region *</label>
                <select name="region" id="edit_region" class="form-select" required>
                    <option value="coastal">Coastal</option>
                    <option value="inland">Inland</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Provinces *</label>
                <input type="text" name="provinces" id="edit_provinces" class="form-input" required>
                <small class="form-help">Comma-separated list</small>
            </div>
            <div class="form-group">
                <label class="form-label">Position *</label>
                <input type="text" name="position" id="edit_position" class="form-input" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Coordinator</button>
        </div>
    </form>
</div>

<script>
function openAddModal() {
    document.getElementById('modalOverlay').classList.add('active');
    document.getElementById('addModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function editCoordinator(coord) {
    document.getElementById('edit_id').value = coord.id;
    document.getElementById('edit_name').value = coord.name;
    document.getElementById('edit_email').value = coord.email;
    document.getElementById('edit_phone').value = coord.phone || '';
    document.getElementById('edit_region').value = coord.region;
    document.getElementById('edit_provinces').value = coord.provinces;
    document.getElementById('edit_position').value = coord.position;
    
    document.getElementById('modalOverlay').classList.add('active');
    document.getElementById('editModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.getElementById('addModal').classList.remove('active');
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
