<?php
$page_title = 'Settings';
require_once 'bootstrap.php';
require_once 'config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>⚙️ Settings</h1>
        <p>Manage your preferences and system settings</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>User Preferences</h3>
                </div>
                <div class="card-body">
                    <form>
                        <div class="form-group">
                            <label>Dashboard Layout</label>
                            <select class="form-control">
                                <option>Compact View</option>
                                <option selected>Standard View</option>
                                <option>Detailed View</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Notifications</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" checked>
                                <label class="form-check-label">Email notifications for new assignments</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" checked>
                                <label class="form-check-label">Email notifications for status updates</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input">
                                <label class="form-check-label">SMS notifications for urgent calls</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Items per page</label>
                            <select class="form-control">
                                <option>10</option>
                                <option>25</option>
                                <option selected>50</option>
                                <option>100</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>System Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>System:</strong> MCLS v1.0</p>
                    <p><strong>Environment:</strong> <?php echo defined('LOCAL_TESTING') && LOCAL_TESTING ? 'Local Testing' : 'Production'; ?></p>
                    <p><strong>Your Role:</strong> <?php echo ucfirst($current_user['role']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($current_user['department_name'] ?? 'Not assigned'); ?></p>
                </div>
            </div>
            
            <?php if ($session->hasRole('admin')): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3>Admin Settings</h3>
                </div>
                <div class="card-body">
                    <p>Administrator-level settings and system configuration.</p>
                    <a href="admin/index.php" class="btn btn-outline">System Administration</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>