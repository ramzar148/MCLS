<?php
$page_title = 'Create Work Order';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only managers and admins can create work orders
if (!$session->hasRole('manager')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to create work orders'
    ];
    header('Location: index.php');
    exit;
}

$current_user = $session->getCurrentUser();
$call_id = (int)($_GET['call_id'] ?? 0);

// Get reference data
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get maintenance call if specified
    $maintenance_call = null;
    if ($call_id) {
        $stmt = $pdo->prepare("
            SELECT mc.*, CONCAT(u.first_name, ' ', u.last_name) as reported_by_name
            FROM maintenance_calls mc
            LEFT JOIN users u ON mc.reported_by = u.id
            WHERE mc.id = ?
        ");
        $stmt->execute([$call_id]);
        $maintenance_call = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all maintenance calls for dropdown
    $stmt = $pdo->query("
        SELECT id, call_number, title, status 
        FROM maintenance_calls 
        WHERE status NOT IN ('closed', 'cancelled')
        ORDER BY reported_date DESC
    ");
    $maintenance_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get technicians for assignment
    $stmt = $pdo->query("
        SELECT id, first_name, last_name 
        FROM users 
        WHERE role IN ('technician', 'manager') AND status = 'active' 
        ORDER BY first_name
    ");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $maintenance_call_id = (int)$_POST['maintenance_call_id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $scheduled_start = !empty($_POST['scheduled_start']) ? $_POST['scheduled_start'] : null;
        $scheduled_end = !empty($_POST['scheduled_end']) ? $_POST['scheduled_end'] : null;
        $materials_needed = trim($_POST['materials_needed'] ?? '');
        $tools_required = trim($_POST['tools_required'] ?? '');
        $safety_requirements = trim($_POST['safety_requirements'] ?? '');
        $estimated_cost = !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null;
        
        // Validation
        $errors = [];
        if (!$maintenance_call_id) $errors[] = "Maintenance call is required";
        if (empty($title)) $errors[] = "Title is required";
        if (empty($description)) $errors[] = "Description is required";
        
        if (empty($errors)) {
            // Generate work order number (WO-YYYY-####)
            $year = date('Y');
            $stmt = $pdo->prepare("
                SELECT MAX(CAST(SUBSTRING(work_order_number, 9) AS UNSIGNED)) as max_num 
                FROM work_orders 
                WHERE work_order_number LIKE ?
            ");
            $stmt->execute(['WO-' . $year . '-%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result['max_num'] ?? 0) + 1;
            $work_order_number = sprintf('WO-%s-%04d', $year, $next_num);
            
            // Insert work order
            $sql = "INSERT INTO work_orders (
                work_order_number, maintenance_call_id, title, description,
                assigned_to, scheduled_start, scheduled_end, materials_needed,
                tools_required, safety_requirements, estimated_cost, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $work_order_number,
                $maintenance_call_id,
                $title,
                $description,
                $assigned_to,
                $scheduled_start,
                $scheduled_end,
                $materials_needed,
                $tools_required,
                $safety_requirements,
                $estimated_cost
            ]);
            
            $work_order_id = $pdo->lastInsertId();
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Work order {$work_order_number} created successfully!"
            ];
            
            header('Location: view.php?id=' . $work_order_id);
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = "Error creating work order: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>🛠️ Create Work Order</h1>
        <p>Create a new work order for maintenance tasks</p>
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
            <div class="form-section">
                <h3>Work Order Details</h3>
                
                <div class="form-group">
                    <label for="maintenance_call_id">Maintenance Call <span class="text-danger">*</span></label>
                    <select class="form-control" id="maintenance_call_id" name="maintenance_call_id" required onchange="updateCallInfo(this)">
                        <option value="">Select Maintenance Call</option>
                        <?php foreach ($maintenance_calls as $mc): ?>
                            <option value="<?php echo $mc['id']; ?>" 
                                    data-title="<?php echo htmlspecialchars($mc['title']); ?>"
                                    <?php echo ($call_id && $call_id == $mc['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mc['call_number']); ?> - 
                                <?php echo htmlspecialchars($mc['title']); ?>
                                (<?php echo ucfirst($mc['status']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Work Order Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo $maintenance_call ? htmlspecialchars($maintenance_call['title']) : ''; ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="4" required><?php echo $maintenance_call ? htmlspecialchars($maintenance_call['description']) : ''; ?></textarea>
                    <small class="form-text">Detailed description of the work to be performed</small>
                </div>
            </div>

            <div class="form-section">
                <h3>Assignment & Schedule</h3>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="assigned_to">Assign To</label>
                        <select class="form-control" id="assigned_to" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="scheduled_start">Scheduled Start Date</label>
                        <input type="datetime-local" class="form-control" id="scheduled_start" name="scheduled_start">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="scheduled_end">Scheduled End Date</label>
                        <input type="datetime-local" class="form-control" id="scheduled_end" name="scheduled_end">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Resources & Requirements</h3>
                
                <div class="form-group">
                    <label for="materials_needed">Materials Needed</label>
                    <textarea class="form-control" id="materials_needed" name="materials_needed" 
                              rows="3" placeholder="List materials and parts required"></textarea>
                </div>

                <div class="form-group">
                    <label for="tools_required">Tools Required</label>
                    <textarea class="form-control" id="tools_required" name="tools_required" 
                              rows="3" placeholder="List tools and equipment needed"></textarea>
                </div>

                <div class="form-group">
                    <label for="safety_requirements">Safety Requirements</label>
                    <textarea class="form-control" id="safety_requirements" name="safety_requirements" 
                              rows="3" placeholder="Safety precautions and PPE required"></textarea>
                </div>

                <div class="form-group">
                    <label for="estimated_cost">Estimated Cost (R)</label>
                    <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" 
                           step="0.01" min="0" placeholder="0.00">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span> Create Work Order
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
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
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
        gap: 0;
    }
    .col-md-6 {
        flex: 0 0 100%;
    }
}
</style>

<script>
function updateCallInfo(select) {
    const selectedOption = select.options[select.selectedIndex];
    const callTitle = selectedOption.getAttribute('data-title');
    
    if (callTitle) {
        document.getElementById('title').value = callTitle;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
