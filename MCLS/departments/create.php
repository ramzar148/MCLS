<?php
$page_title = 'Create Department';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only admins can create departments
if (!$session->hasRole('admin')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page'
    ];
    header('Location: index.php');
    exit;
}

// Get managers for dropdown
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query("
        SELECT id, first_name, last_name 
        FROM users 
        WHERE role IN ('manager', 'admin') AND status = 'active' 
        ORDER BY first_name, last_name
    ");
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = "Department name is required";
        
        // Check for duplicate name
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $errors[] = "A department with this name already exists";
        }
        
        if (empty($errors)) {
            // Insert department
            $stmt = $pdo->prepare("
                INSERT INTO departments (name, description, manager_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$name, $description, $manager_id, $status]);
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Department created successfully!'
            ];
            
            header('Location: index.php');
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = "Error creating department: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>➕ Create Department</h1>
        <p>Add a new department to the system</p>
    </div>
    <div class="page-actions">
        <a href="index.php" class="btn btn-secondary">← Back to List</a>
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
            <div class="form-group">
                <label for="name">Department Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                       required maxlength="255">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" 
                          rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="manager_id">Department Manager</label>
                <select class="form-control" id="manager_id" name="manager_id">
                    <option value="">Not assigned</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?php echo $manager['id']; ?>"
                                <?php echo (isset($_POST['manager_id']) && $_POST['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span> Create Department
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
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
    max-width: 800px;
}

.card-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #495057;
}

.text-danger { color: #dc3545; }

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

textarea.form-control {
    resize: vertical;
    font-family: inherit;
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
</style>

<?php require_once '../includes/footer.php'; ?>
