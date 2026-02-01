<?php
$page_title = 'View Work Order';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();
$work_order_id = (int)($_GET['id'] ?? 0);

if (!$work_order_id) {
    header('Location: index.php');
    exit;
}

// Get work order details
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "SELECT 
                wo.*,
                mc.call_number as maintenance_call_number,
                mc.title as maintenance_call_title,
                mc.description as maintenance_call_description,
                CONCAT(u1.first_name, ' ', u1.last_name) as assigned_to_name,
                u1.email as assignee_email,
                CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name,
                u2.email as approver_email
            FROM work_orders wo
            LEFT JOIN maintenance_calls mc ON wo.maintenance_call_id = mc.id
            LEFT JOIN users u1 ON wo.assigned_to = u1.id
            LEFT JOIN users u2 ON wo.approved_by = u2.id
            WHERE wo.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$work_order_id]);
    $work_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$work_order) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Work order not found'
        ];
        header('Location: index.php');
        exit;
    }
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>🛠️ Work Order Details</h1>
        <p class="wo-number">WO #<?php echo htmlspecialchars($work_order['work_order_number']); ?></p>
    </div>
    <div class="page-actions">
        <a href="index.php" class="btn btn-secondary">← Back to List</a>
        <?php if ($session->hasRole('manager') && $work_order['status'] !== 'completed' && $work_order['status'] !== 'cancelled'): ?>
            <a href="edit.php?id=<?php echo $work_order['id']; ?>" class="btn btn-primary">✏️ Edit</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- Main Details Card -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($work_order['title']); ?></h3>
                <span class="badge badge-status badge-status-<?php echo $work_order['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $work_order['status'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="detail-section">
                    <h4>Related Maintenance Call</h4>
                    <p>
                        <a href="../maintenance_calls/view.php?id=<?php echo $work_order['maintenance_call_id']; ?>">
                            <?php echo htmlspecialchars($work_order['maintenance_call_number']); ?> - 
                            <?php echo htmlspecialchars($work_order['maintenance_call_title']); ?>
                        </a>
                    </p>
                </div>

                <div class="detail-section">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($work_order['description'])); ?></p>
                </div>

                <?php if ($work_order['materials_needed']): ?>
                <div class="detail-section">
                    <h4>Materials Needed</h4>
                    <p><?php echo nl2br(htmlspecialchars($work_order['materials_needed'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($work_order['tools_required']): ?>
                <div class="detail-section">
                    <h4>Tools Required</h4>
                    <p><?php echo nl2br(htmlspecialchars($work_order['tools_required'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($work_order['safety_requirements']): ?>
                <div class="detail-section">
                    <h4>⚠️ Safety Requirements</h4>
                    <p class="safety-alert"><?php echo nl2br(htmlspecialchars($work_order['safety_requirements'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($work_order['completion_notes']): ?>
                <div class="detail-section">
                    <h4>Completion Notes</h4>
                    <p><?php echo nl2br(htmlspecialchars($work_order['completion_notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Info Sidebar -->
        <div class="card">
            <div class="card-header">
                <h3>Information</h3>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <label>Status</label>
                    <span class="badge badge-status badge-status-<?php echo $work_order['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $work_order['status'])); ?>
                    </span>
                </div>

                <?php if ($work_order['assigned_to_name']): ?>
                <div class="info-item">
                    <label>Assigned To</label>
                    <div><?php echo htmlspecialchars($work_order['assigned_to_name']); ?></div>
                    <?php if ($work_order['assignee_email']): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars($work_order['assignee_email']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($work_order['approved_by_name']): ?>
                <div class="info-item">
                    <label>Approved By</label>
                    <div><?php echo htmlspecialchars($work_order['approved_by_name']); ?></div>
                    <?php if ($work_order['approved_date']): ?>
                        <div class="text-muted small"><?php echo date('M d, Y g:i A', strtotime($work_order['approved_date'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($work_order['scheduled_start'] || $work_order['scheduled_end']): ?>
                <div class="info-item">
                    <label>Scheduled</label>
                    <?php if ($work_order['scheduled_start']): ?>
                        <div><strong>Start:</strong> <?php echo date('M d, Y g:i A', strtotime($work_order['scheduled_start'])); ?></div>
                    <?php endif; ?>
                    <?php if ($work_order['scheduled_end']): ?>
                        <div><strong>End:</strong> <?php echo date('M d, Y g:i A', strtotime($work_order['scheduled_end'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($work_order['actual_start'] || $work_order['actual_end']): ?>
                <div class="info-item">
                    <label>Actual Time</label>
                    <?php if ($work_order['actual_start']): ?>
                        <div><strong>Start:</strong> <?php echo date('M d, Y g:i A', strtotime($work_order['actual_start'])); ?></div>
                    <?php endif; ?>
                    <?php if ($work_order['actual_end']): ?>
                        <div><strong>End:</strong> <?php echo date('M d, Y g:i A', strtotime($work_order['actual_end'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <label>Cost</label>
                    <?php if ($work_order['actual_cost']): ?>
                        <div class="cost-actual">R <?php echo number_format($work_order['actual_cost'], 2); ?></div>
                    <?php endif; ?>
                    <?php if ($work_order['estimated_cost']): ?>
                        <div class="cost-estimate">Est: R <?php echo number_format($work_order['estimated_cost'], 2); ?></div>
                    <?php endif; ?>
                    <?php if (!$work_order['actual_cost'] && !$work_order['estimated_cost']): ?>
                        <div class="text-muted">No cost information</div>
                    <?php endif; ?>
                </div>

                <div class="info-item">
                    <label>Created</label>
                    <div><?php echo date('M d, Y', strtotime($work_order['created_at'])); ?></div>
                    <div class="text-muted small"><?php echo date('g:i A', strtotime($work_order['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if ($session->hasRole('manager') || ($session->hasRole('technician') && $work_order['assigned_to'] == $current_user['id'])): ?>
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <?php if ($session->hasRole('manager')): ?>
                    <?php if ($work_order['status'] === 'pending'): ?>
                        <button class="btn btn-success btn-block" onclick="approveWorkOrder(<?php echo $work_order['id']; ?>)">
                            ✅ Approve Work Order
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($work_order['assigned_to'] == $current_user['id']): ?>
                    <?php if ($work_order['status'] === 'approved'): ?>
                        <button class="btn btn-primary btn-block" onclick="updateWOStatus(<?php echo $work_order['id']; ?>, 'in_progress')">
                            ▶️ Start Work
                        </button>
                    <?php endif; ?>

                    <?php if ($work_order['status'] === 'in_progress'): ?>
                        <button class="btn btn-success btn-block" onclick="completeWorkOrder(<?php echo $work_order['id']; ?>)">
                            ✅ Mark Complete
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 20px;
}

.page-header h1 {
    margin: 0 0 5px 0;
    font-size: 1.75rem;
}

.wo-number {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 600;
    margin: 0;
}

.row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.col-md-8 { flex: 0 0 66%; }
.col-md-4 { flex: 0 0 32%; }

.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 20px;
}

.card-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #1e6b3e 0%, #2d8f5a 100%);
    color: white;
    border-radius: 6px 6px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.card-body {
    padding: 20px;
}

.detail-section {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.detail-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.detail-section h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-section p {
    margin: 0;
    line-height: 1.6;
    color: #495057;
}

.safety-alert {
    background: #fff3cd;
    border-left: 3px solid #ffc107;
    padding: 12px;
    border-radius: 4px;
}

.info-item {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f3f5;
}

.info-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.info-item label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.badge-status {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-status-pending { background: #ffc107; color: #000; }
.badge-status-approved { background: #17a2b8; color: white; }
.badge-status-in_progress { background: #007bff; color: white; }
.badge-status-completed { background: #28a745; color: white; }
.badge-status-cancelled { background: #6c757d; color: white; }

.cost-actual {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-color);
}

.cost-estimate {
    font-size: 0.9rem;
    color: #6c757d;
}

.text-muted { color: #6c757d; }
.small { font-size: 0.85rem; }

.btn-block {
    width: 100%;
    margin-bottom: 10px;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .row {
        flex-direction: column;
    }
    .col-md-8, .col-md-4 {
        flex: 0 0 100%;
    }
}
</style>

<script>
async function approveWorkOrder(woId) {
    if (!confirm('Approve this work order?')) return;
    
    try {
        const response = await fetch('ajax/approve.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                work_order_id: woId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Work order approved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Approval failed'));
        }
    } catch (error) {
        console.error('Approval error:', error);
        alert('Network error. Please try again.');
    }
}

async function updateWOStatus(woId, status) {
    const messages = {
        'in_progress': 'Start working on this order?',
        'completed': 'Mark this work order as completed?'
    };
    
    if (!confirm(messages[status])) return;
    
    try {
        const response = await fetch('ajax/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                work_order_id: woId,
                status: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Status updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Update failed'));
        }
    } catch (error) {
        console.error('Status update error:', error);
        alert('Network error. Please try again.');
    }
}

function completeWorkOrder(woId) {
    // Could open a modal for completion notes and actual cost
    updateWOStatus(woId, 'completed');
}
</script>

<?php require_once '../includes/footer.php'; ?>
