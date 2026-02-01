<?php
$page_title = 'Audit Log';
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

// Get audit log entries
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT al.*, u.first_name, u.last_name, u.ad_username
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $audit_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $audit_entries = [];
    $error_message = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>🔒 Audit Log</h1>
        <p>System activity and security audit trail</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
        </div>
        <div class="card-body">
            <?php if (empty($audit_entries)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔒</div>
                    <h3>No Audit Entries Found</h3>
                    <p>System activity will appear here as users interact with the system.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_entries as $entry): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i:s', strtotime($entry['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($entry['first_name']) {
                                            echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']);
                                            echo '<br><small class="text-muted">' . htmlspecialchars($entry['ad_username']) . '</small>';
                                        } else {
                                            echo '<em>System</em>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $entry['action'] == 'INSERT' ? 'success' : 
                                                ($entry['action'] == 'UPDATE' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $entry['action']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($entry['table_name']); ?></td>
                                    <td><?php echo $entry['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($entry['ip_address'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($entry['old_values'] || $entry['new_values']): ?>
                                            <button class="btn btn-sm btn-outline" onclick="showDetails(<?php echo htmlspecialchars(json_encode($entry)); ?>)">
                                                View Details
                                            </button>
                                        <?php else: ?>
                                            <em>No details</em>
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

    <!-- Audit Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">📝</div>
                <div class="stats-content">
                    <h3><?php echo count($audit_entries); ?></h3>
                    <p>Recent Entries</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">✅</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($audit_entries, fn($e) => $e['action'] == 'INSERT')); ?></h3>
                    <p>Records Created</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">📝</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($audit_entries, fn($e) => $e['action'] == 'UPDATE')); ?></h3>
                    <p>Records Updated</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon">🗑️</div>
                <div class="stats-content">
                    <h3><?php echo count(array_filter($audit_entries, fn($e) => $e['action'] == 'DELETE')); ?></h3>
                    <p>Records Deleted</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for audit details -->
<div class="modal" id="auditModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Audit Entry Details</h3>
            <button class="btn-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="auditDetails">
            <!-- Details will be populated by JavaScript -->
        </div>
    </div>
</div>

<script>
function showDetails(entry) {
    const modal = document.getElementById('auditModal');
    const details = document.getElementById('auditDetails');
    
    let html = '<h4>Entry Information</h4>';
    html += '<p><strong>Timestamp:</strong> ' + entry.created_at + '</p>';
    html += '<p><strong>Action:</strong> ' + entry.action + '</p>';
    html += '<p><strong>Table:</strong> ' + entry.table_name + '</p>';
    html += '<p><strong>Record ID:</strong> ' + entry.record_id + '</p>';
    
    if (entry.old_values) {
        html += '<h5>Old Values</h5>';
        html += '<pre>' + JSON.stringify(JSON.parse(entry.old_values), null, 2) + '</pre>';
    }
    
    if (entry.new_values) {
        html += '<h5>New Values</h5>';
        html += '<pre>' + JSON.stringify(JSON.parse(entry.new_values), null, 2) + '</pre>';
    }
    
    details.innerHTML = html;
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('auditModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('auditModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
}

.modal-header {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}
</style>

<?php require_once '../includes/footer.php'; ?>