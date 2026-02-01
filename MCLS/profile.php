<?php
$page_title = 'My Profile';
require_once 'bootstrap.php';
require_once 'config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>👤 My Profile</h1>
        <p>Manage your account information and preferences</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Username</label>
                        <p class="form-text"><?php echo htmlspecialchars($current_user['username']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <p class="form-text"><?php echo htmlspecialchars($current_user['full_name']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <p class="form-text"><?php echo htmlspecialchars($current_user['email']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <p class="form-text">
                            <span class="badge badge-primary"><?php echo ucfirst($current_user['role']); ?></span>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Login</label>
                        <p class="form-text"><?php echo date('F j, Y \a\t g:i A', $current_user['login_time']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <p>Profile management is handled through Active Directory. Contact your system administrator for:</p>
                    <ul>
                        <li>Password changes</li>
                        <li>Email updates</li>
                        <li>Role modifications</li>
                        <li>Department transfers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>