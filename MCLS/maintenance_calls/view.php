<?php
$page_title = 'View Maintenance Call';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();
$call_id = (int)($_GET['id'] ?? 0);

if (!$call_id) {
    header('Location: index.php');
    exit;
}

// Get call details
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "SELECT 
                mc.*,
                pl.name as priority_name,
                pl.color_code as priority_color,
                CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                u1.email as reporter_email,
                CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
                u2.email as assignee_email,
                e.name as equipment_name,
                e.asset_number,
                d.name as department_name,
                DATEDIFF(NOW(), mc.reported_date) as days_open
            FROM maintenance_calls mc
            LEFT JOIN priority_levels pl ON mc.priority_id = pl.id
            LEFT JOIN users u1 ON mc.reported_by = u1.id
            LEFT JOIN users u2 ON mc.assigned_to = u2.id
            LEFT JOIN equipment e ON mc.equipment_id = e.id
            LEFT JOIN departments d ON mc.department_id = d.id
            WHERE mc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$call_id]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Maintenance call not found'
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
        <h1>📋 Maintenance Call Details</h1>
        <p class="call-number">Call #<?php echo htmlspecialchars($call['call_number']); ?></p>
    </div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-success">🖨️ Print</button>
        <a href="index.php" class="btn btn-secondary">← Back to List</a>
        <?php if ($session->hasRole('manager')): ?>
            <a href="edit.php?id=<?php echo $call['id']; ?>" class="btn btn-primary">✏️ Edit</a>
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
                <h3><?php echo htmlspecialchars($call['title']); ?></h3>
                <div class="badges">
                    <span class="badge badge-status badge-status-<?php echo $call['status']; ?>">
                        <?php echo ucfirst($call['status']); ?>
                    </span>
                    <span class="badge badge-priority" style="background-color: <?php echo htmlspecialchars($call['priority_color'] ?? '#6c757d'); ?>">
                        <?php echo htmlspecialchars($call['priority_name']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="detail-section">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($call['description'])); ?></p>
                </div>

                <?php if ($call['location']): ?>
                <div class="detail-section">
                    <h4>📍 Location</h4>
                    <p><strong>Office/Building:</strong> <?php echo htmlspecialchars($call['location']); ?></p>
                    <?php if (!empty($call['province'])): ?>
                    <p><strong>Province:</strong> <?php echo htmlspecialchars($call['province']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($call['region'])): ?>
                    <p><strong>Region:</strong> 
                        <span class="badge badge-<?php echo $call['region'] === 'coastal' ? 'info' : 'secondary'; ?>">
                            <?php echo ucfirst($call['region']); ?>
                        </span>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($call['call_type'])): ?>
                <div class="detail-section">
                    <h4>🔧 Call Type</h4>
                    <p><?php echo htmlspecialchars($call['call_type']); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($call['reporter_name'])): ?>
                <div class="detail-section">
                    <h4>� Reporting Official</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($call['reporter_name']); ?></p>
                    <?php if (!empty($call['reporter_contact'])): ?>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($call['reporter_contact']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($call['equipment_name']): ?>
                <div class="detail-section">
                    <h4>⚙️ Equipment</h4>
                    <p><?php echo htmlspecialchars($call['equipment_name']); ?>
                    <?php if ($call['asset_number']): ?>
                        <span class="text-muted">(<?php echo htmlspecialchars($call['asset_number']); ?>)</span>
                    <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline/Activity Card -->
        <div class="card">
            <div class="card-header">
                <h3>Activity Timeline</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">📝</div>
                        <div class="timeline-content">
                            <strong>Call Created</strong>
                            <p>Reported by <?php echo htmlspecialchars($call['reported_by_name']); ?></p>
                            <span class="timeline-date"><?php echo date('M d, Y g:i A', strtotime($call['reported_date'])); ?></span>
                        </div>
                    </div>

                    <?php if ($call['assigned_to']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">👤</div>
                        <div class="timeline-content">
                            <strong>Assigned</strong>
                            <p>Assigned to <?php echo htmlspecialchars($call['assigned_to_name']); ?></p>
                            <span class="timeline-date"><?php echo date('M d, Y g:i A', strtotime($call['updated_at'])); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($call['resolved_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">✅</div>
                        <div class="timeline-content">
                            <strong>Resolved</strong>
                            <p>Issue marked as resolved</p>
                            <span class="timeline-date"><?php echo date('M d, Y g:i A', strtotime($call['resolved_at'])); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Comments/Updates Card -->
        <div class="card">
            <div class="card-header">
                <h3>Comments & Updates</h3>
            </div>
            <div class="card-body">
                <?php
                // Get comments for this call
                try {
                    $stmt = $pdo->prepare("
                        SELECT cc.*, CONCAT(u.first_name, ' ', u.last_name) as commenter_name, u.role
                        FROM call_comments cc
                        LEFT JOIN users u ON cc.user_id = u.id
                        WHERE cc.call_id = ?
                        ORDER BY cc.created_at ASC
                    ");
                    $stmt->execute([$call_id]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $comments = [];
                }
                ?>

                <?php if (!empty($comments)): ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                <span class="badge badge-role"><?php echo ucfirst($comment['role']); ?></span>
                                <span class="comment-date"><?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-body">
                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No comments yet. Be the first to add an update!</p>
                <?php endif; ?>

                <!-- Add Comment Form -->
                <?php if ($call['status'] !== 'closed' && $call['status'] !== 'cancelled'): ?>
                <div class="add-comment-section">
                    <h4>Add Comment</h4>
                    <form id="addCommentForm" onsubmit="addComment(event)">
                        <textarea class="form-control" id="commentText" name="comment" rows="3" 
                                  placeholder="Add an update or comment..." required></textarea>
                        <button type="submit" class="btn btn-primary">💬 Post Comment</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attachments Card -->
        <div class="card">
            <div class="card-header">
                <h3>📎 Attachments</h3>
            </div>
            <div class="card-body">
                <?php
                // Get attachments for this call
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
                        FROM attachments a
                        LEFT JOIN users u ON a.uploaded_by = u.id
                        WHERE a.entity_type = 'maintenance_call' AND a.entity_id = ?
                        ORDER BY a.created_at DESC
                    ");
                    $stmt->execute([$call_id]);
                    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $attachments = [];
                }
                ?>

                <div id="attachments-list">
                    <?php if (!empty($attachments)): ?>
                        <?php foreach ($attachments as $att): ?>
                        <div class="attachment-item">
                            <div class="attachment-icon">
                                <?php
                                if (strpos($att['mime_type'], 'image/') === 0) {
                                    echo '🖼️';
                                } elseif ($att['mime_type'] === 'application/pdf') {
                                    echo '📄';
                                } else {
                                    echo '📎';
                                }
                                ?>
                            </div>
                            <div class="attachment-info">
                                <div class="attachment-name"><?php echo htmlspecialchars($att['original_filename']); ?></div>
                                <div class="attachment-meta">
                                    <?php echo number_format($att['file_size'] / 1024, 1); ?> KB · 
                                    <?php echo htmlspecialchars($att['uploaded_by_name']); ?> · 
                                    <?php echo date('M d, Y', strtotime($att['created_at'])); ?>
                                </div>
                            </div>
                            <a href="../<?php echo htmlspecialchars($att['file_path']); ?>" 
                               class="btn btn-sm btn-primary" download>⬇️</a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No attachments yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Upload Form -->
                <?php if ($call['status'] !== 'closed' && $call['status'] !== 'cancelled'): ?>
                <div class="upload-section">
                    <h4>Upload File</h4>
                    <form id="uploadForm" onsubmit="uploadFile(event)">
                        <input type="file" class="form-control" id="fileInput" name="file" 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx" required>
                        <small class="form-text">Max 5MB. Allowed: Images, PDF, Office documents</small>
                        <button type="submit" class="btn btn-primary">📤 Upload</button>
                    </form>
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
                    <span class="badge badge-status badge-status-<?php echo $call['status']; ?>">
                        <?php echo ucfirst($call['status']); ?>
                    </span>
                </div>

                <div class="info-item">
                    <label>Priority</label>
                    <span class="badge badge-priority" style="background-color: <?php echo htmlspecialchars($call['priority_color'] ?? '#6c757d'); ?>">
                        <?php echo htmlspecialchars($call['priority_name']); ?>
                    </span>
                </div>

                <div class="info-item">
                    <label>Reported By</label>
                    <div><?php echo htmlspecialchars($call['reported_by_name']); ?></div>
                    <?php if ($call['reporter_email']): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars($call['reporter_email']); ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($call['assigned_to_name']): ?>
                <div class="info-item">
                    <label>Assigned To</label>
                    <div><?php echo htmlspecialchars($call['assigned_to_name']); ?></div>
                    <?php if ($call['assignee_email']): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars($call['assignee_email']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($call['department_name']): ?>
                <div class="info-item">
                    <label>Department</label>
                    <div><?php echo htmlspecialchars($call['department_name']); ?></div>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <label>Reported Date</label>
                    <div><?php echo date('M d, Y', strtotime($call['reported_date'])); ?></div>
                    <div class="text-muted small"><?php echo date('g:i A', strtotime($call['reported_date'])); ?></div>
                </div>

                <?php if ($call['response_time_minutes']): ?>
                <div class="info-item">
                    <label>⏱️ Response Time</label>
                    <div>
                        <?php 
                        $hours = floor($call['response_time_minutes'] / 60);
                        $minutes = $call['response_time_minutes'] % 60;
                        if ($hours > 0) {
                            echo $hours . ' hour' . ($hours > 1 ? 's' : '');
                            if ($minutes > 0) echo ' ' . $minutes . ' min';
                        } else {
                            echo $minutes . ' minutes';
                        }
                        ?>
                    </div>
                    <div class="text-muted small">
                        <?php
                        // Color code based on priority
                        $response_class = 'success';
                        if ($call['response_time_minutes'] > 1440) { // >24 hours
                            $response_class = 'danger';
                        } elseif ($call['response_time_minutes'] > 480) { // >8 hours
                            $response_class = 'warning';
                        }
                        ?>
                        <span class="badge badge-<?php echo $response_class; ?>">
                            Time to assignment
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <label>Days Open</label>
                    <div><?php echo $call['days_open']; ?> days</div>
                </div>

                <?php if ($call['resolved_at']): ?>
                <div class="info-item">
                    <label>Resolved Date</label>
                    <div><?php echo date('M d, Y', strtotime($call['resolved_at'])); ?></div>
                    <div class="text-muted small"><?php echo date('g:i A', strtotime($call['resolved_at'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if ($session->hasRole('manager') || $session->hasRole('technician')): ?>
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <?php if ($session->hasRole('manager')): ?>
                    <!-- Manager Actions -->
                    <?php if ($call['status'] !== 'closed' && $call['status'] !== 'cancelled'): ?>
                        <a href="edit.php?id=<?php echo $call['id']; ?>" class="btn btn-primary btn-block">
                            ✏️ Edit Call
                        </a>
                        
                        <a href="../work_orders/create.php?call_id=<?php echo $call['id']; ?>" class="btn btn-success btn-block">
                            🛠️ Create Work Order
                        </a>
                        
                        <?php if (!$call['assigned_to']): ?>
                            <button class="btn btn-success btn-block" onclick="showAssignModal()">
                                👤 Assign to Technician
                            </button>
                        <?php else: ?>
                            <button class="btn btn-warning btn-block" onclick="showAssignModal()">
                                🔄 Reassign
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-info btn-block" onclick="showStatusModal()">
                            📊 Update Status
                        </button>
                    <?php endif; ?>
                    
                <?php elseif ($session->hasRole('technician')): ?>
                    <!-- Technician Actions -->
                    <?php if (!$call['assigned_to'] && $call['status'] === 'open'): ?>
                        <button class="btn btn-primary btn-block" onclick="assignToMe(<?php echo $call['id']; ?>)">
                            👤 Assign to Me
                        </button>
                    <?php endif; ?>

                    <?php if ($call['assigned_to'] == $current_user['id']): ?>
                        <?php if ($call['status'] === 'open' || $call['status'] === 'assigned'): ?>
                            <button class="btn btn-success btn-block" onclick="updateStatus(<?php echo $call['id']; ?>, 'in_progress')">
                                ▶️ Start Working
                            </button>
                        <?php endif; ?>

                        <?php if ($call['status'] === 'in_progress'): ?>
                            <button class="btn btn-success btn-block" onclick="updateStatus(<?php echo $call['id']; ?>, 'resolved')">
                                ✅ Mark as Resolved
                            </button>
                        <?php endif; ?>
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

.call-number {
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

.col-md-8 {
    flex: 0 0 66.666%;
}

.col-md-4 {
    flex: 0 0 33.333%;
}

.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 20px;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #1e6b3e 0%, #2d8f5a 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: white;
}

.card-body {
    padding: 20px;
}

.badges {
    display: flex;
    gap: 8px;
}

.badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
}

.badge-status-open { background: #ffc107; }
.badge-status-assigned { background: #17a2b8; }
.badge-status-in_progress { background: #007bff; }
.badge-status-resolved { background: #28a745; }
.badge-status-closed { background: #6c757d; }
.badge-status-cancelled { background: #dc3545; }

.detail-section {
    margin-bottom: 24px;
}

.detail-section:last-child {
    margin-bottom: 0;
}

.detail-section h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-section p {
    margin: 0;
    line-height: 1.6;
}

.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 24px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -40px;
    width: 32px;
    height: 32px;
    background: white;
    border: 2px solid var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.timeline-content strong {
    display: block;
    margin-bottom: 4px;
    color: #495057;
}

.timeline-content p {
    margin: 0 0 4px 0;
    color: #6c757d;
}

.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
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

.info-item > div {
    color: #495057;
    font-weight: 500;
}

.text-muted {
    color: #6c757d;
}

.small {
    font-size: 0.85rem;
}

.btn-block {
    display: block;
    width: 100%;
    margin-bottom: 8px;
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

<!-- Assignment Modal for Managers -->
<?php if ($session->hasRole('manager')): ?>
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Call to Technician</h3>
            <span class="modal-close" onclick="closeAssignModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="assignForm">
                <div class="form-group">
                    <label for="technician_id">Select Technician</label>
                    <select id="technician_id" name="technician_id" class="form-control" required>
                        <option value="">-- Select Technician --</option>
                        <?php
                        // Get technicians
                        $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users WHERE role IN ('technician', 'manager') AND status = 'active' ORDER BY first_name");
                        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($technicians as $tech):
                        ?>
                            <option value="<?php echo $tech['id']; ?>" <?php echo ($call['assigned_to'] == $tech['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Assign</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    border-radius: 8px;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 20px;
    background: linear-gradient(135deg, #1e6b3e 0%, #2d8f5a 100%);
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: white;
}

.modal-close:hover {
    opacity: 0.8;
}

.modal-body {
    padding: 24px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

/* Comments Section */
.comments-list {
    margin-bottom: 24px;
    max-height: 400px;
    overflow-y: auto;
}

.comment-item {
    background: #f8f9fa;
    border-left: 3px solid var(--primary-color);
    padding: 12px 16px;
    margin-bottom: 12px;
    border-radius: 4px;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.comment-header strong {
    color: #495057;
}

.badge-role {
    font-size: 0.7rem;
    padding: 2px 8px;
    background: var(--primary-color);
    color: white;
    border-radius: 12px;
}

.comment-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-left: auto;
}

.comment-body {
    color: #495057;
    line-height: 1.5;
}

.add-comment-section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
}

.add-comment-section h4 {
    font-size: 1rem;
    margin-bottom: 12px;
    color: #495057;
}

.add-comment-section form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.add-comment-section textarea {
    resize: vertical;
    min-height: 80px;
}

.add-comment-section .btn {
    align-self: flex-start;
}

/* Attachments Section */
.attachment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
}

.attachment-icon {
    font-size: 1.5rem;
}

.attachment-info {
    flex: 1;
}

.attachment-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 2px;
}

.attachment-meta {
    font-size: 0.8rem;
    color: #6c757d;
}

.upload-section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
}

.upload-section h4 {
    font-size: 1rem;
    margin-bottom: 12px;
    color: #495057;
}

.upload-section form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.upload-section .btn {
    align-self: flex-start;
}

/* Print Styles */
@media print {
    .app-sidebar,
    .page-actions,
    .btn,
    button,
    .add-comment-section,
    .upload-section,
    #assignModal {
        display: none !important;
    }
    
    .app-main {
        margin-left: 0 !important;
        padding: 20px !important;
    }
    
    .row {
        display: block;
    }
    
    .col-md-8,
    .col-md-4 {
        width: 100% !important;
        margin-bottom: 20px;
    }
    
    .card {
        page-break-inside: avoid;
        border: 1px solid #000 !important;
    }
    
    .card-header {
        background: #1e6b3e !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .badge-status,
    .badge-priority,
    .badge-role {
        border: 1px solid #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
<?php endif; ?>

<script>
function showAssignModal() {
    document.getElementById('assignModal').style.display = 'block';
}

function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('assignModal');
    if (event.target == modal) {
        closeAssignModal();
    }
}

// Handle assignment form submission
<?php if ($session->hasRole('manager')): ?>
document.getElementById('assignForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const technicianId = document.getElementById('technician_id').value;
    
    if (!technicianId) {
        alert('Please select a technician');
        return;
    }
    
    try {
        const response = await fetch('ajax/assign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                call_id: <?php echo $call['id']; ?>,
                technician_id: technicianId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Call assigned successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Assignment failed'));
        }
    } catch (error) {
        console.error('Assignment error:', error);
        alert('Network error. Please try again.');
    }
});
<?php endif; ?>

async function assignToMe(callId) {
    if (!confirm('Assign this call to yourself?')) return;
    
    try {
        const response = await fetch('ajax/assign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                call_id: callId,
                technician_id: <?php echo $current_user['id']; ?>
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Call assigned to you successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Assignment failed'));
        }
    } catch (error) {
        console.error('Assignment error:', error);
        alert('Network error. Please try again.');
    }
}

async function updateStatus(callId, status) {
    const confirmMessages = {
        'in_progress': 'Start working on this call?',
        'resolved': 'Mark this call as resolved?'
    };
    
    if (!confirm(confirmMessages[status] || 'Update call status?')) return;
    
    try {
        const response = await fetch('ajax/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                call_id: callId,
                status: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Status updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Status update failed'));
        }
    } catch (error) {
        console.error('Status update error:', error);
        alert('Network error. Please try again.');
    }
}

async function addComment(event) {
    event.preventDefault();
    
    const form = event.target;
    const commentText = document.getElementById('commentText').value.trim();
    
    if (!commentText) {
        alert('Please enter a comment');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('call_id', <?php echo $call_id; ?>);
        formData.append('comment', commentText);
        
        const response = await fetch('ajax/add_comment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Comment added successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to add comment'));
        }
    } catch (error) {
        console.error('Comment error:', error);
        alert('Network error. Please try again.');
    }
}

async function uploadFile(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a file');
        return;
    }
    
    // Check file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must not exceed 5MB');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('entity_type', 'maintenance_call');
        formData.append('entity_id', <?php echo $call_id; ?>);
        
        const response = await fetch('../attachments/upload.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('File uploaded successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Upload failed'));
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Network error. Please try again.');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
