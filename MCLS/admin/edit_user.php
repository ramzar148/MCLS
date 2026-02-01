<?php
$page_title = 'Edit User';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only admins can edit users
if (!$session->hasRole('admin')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page'
    ];
    header('Location: users.php');
    exit;
}

$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Get user and reference data
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'User not found'
        ];
        header('Location: users.php');
        exit;
    }
    
    // Get departments
    $stmt = $pdo->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $status = $_POST['status'] ?? 'active';
        $new_password = trim($_POST['new_password'] ?? '');
        
        // Validation
        $errors = [];
        if (empty($first_name)) $errors[] = "First name is required";
        if (empty($last_name)) $errors[] = "Last name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!in_array($role, ['admin', 'manager', 'technician', 'user'])) $errors[] = "Invalid role";
        
        // Check email uniqueness (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Email address already in use";
        }
        
        if (empty($errors)) {
            // Update user
            if ($new_password) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, role = ?, 
                        department_id = ?, status = ?, password_hash = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $role, $department_id, $status, $hashed_password, $user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, role = ?, 
                        department_id = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $role, $department_id, $status, $user_id]);
            }
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'User updated successfully!'
            ];
            
            header('Location: users.php');
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = "Error updating user: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>✏️ Edit User</h1>
        <p>Update user information and permissions</p>
    </div>
    <div class="page-actions">
        <a href="users.php" class="btn btn-secondary">← Back to List</a>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-section">
                <h3>Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                               required maxlength="100">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                               required maxlength="100">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required maxlength="255">
                </div>
            </div>

            <div class="form-section">
                <h3>Role & Permissions</h3>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="role">Role <span class="text-danger">*</span></label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User (Basic Access)</option>
                            <option value="technician" <?php echo $user['role'] === 'technician' ? 'selected' : ''; ?>>Technician</option>
                            <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="department_id">Department</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">Not assigned</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                        <?php echo ($user['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Account Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Password Reset (Optional)</h3>
                <p class="text-muted">Leave blank to keep current password</p>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Enter new password (leave blank to keep current)">
                    <small class="form-text">Minimum 8 characters recommended</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span> Save Changes
                </button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.page-header h1 {
    margin: 0 0 5px 0;
    font-size: 1.75rem;
}

.page-header p {
    margin: 0;
    color: #6c757d;
}

.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    max-width: 900px;
}

.card-body {
    padding: 24px;
}

.form-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 2px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    font-size: 1.1rem;
    color: var(--primary-color);
    margin-bottom: 16px;
}

.form-row {
    display: flex;
    gap: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.col-md-6 { flex: 0 0 50%; }

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #495057;
}

.text-danger { color: #dc3545; }
.text-muted { color: #6c757d; }

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.95rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(30, 107, 62, 0.1);
}

.form-text {
    display: block;
    margin-top: 4px;
    font-size: 0.85rem;
    color: #6c757d;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
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

.alert ul {
    margin: 8px 0 0 20px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    .col-md-6 {
        flex: 0 0 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
