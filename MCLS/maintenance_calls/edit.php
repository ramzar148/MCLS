<?php
$page_title = 'Edit Maintenance Call';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

// Only managers and admins can edit
if (!$session->hasRole('manager')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to edit maintenance calls'
    ];
    header('Location: index.php');
    exit;
}

$current_user = $session->getCurrentUser();
$call_id = (int)($_GET['id'] ?? 0);

if (!$call_id) {
    header('Location: index.php');
    exit;
}

// Get reference data
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get call details
    $stmt = $pdo->prepare("SELECT * FROM maintenance_calls WHERE id = ?");
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
    
    // Get departments
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get priority levels
    $stmt = $pdo->query("SELECT id, name FROM priority_levels ORDER BY level");
    $priority_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get equipment
    $stmt = $pdo->query("SELECT id, name, asset_number FROM equipment WHERE status = 'active' ORDER BY name");
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get technicians for assignment
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role IN ('technician', 'manager') AND status = 'active' ORDER BY first_name");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $call_type = trim($_POST['call_type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $reporter_name = trim($_POST['reporter_name'] ?? '');
        $reporter_contact = trim($_POST['reporter_contact'] ?? '');
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $priority_id = !empty($_POST['priority_id']) ? (int)$_POST['priority_id'] : null;
        $equipment_id = !empty($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $status = $_POST['status'] ?? $call['status'];
        
        // Validation
        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($description)) $errors[] = "Description is required";
        if (empty($call_type)) $errors[] = "Call Type is required";
        if (empty($location)) $errors[] = "Location is required";
        if (empty($province)) $errors[] = "Province is required";
        if (empty($region)) $errors[] = "Region is required";
        if (empty($reporter_name)) $errors[] = "Reporter Name is required";
        if (empty($reporter_contact)) $errors[] = "Reporter Contact is required";
        if (empty($priority_id)) $errors[] = "Priority is required";
        
        if (empty($errors)) {
            // Calculate response time if call is being assigned
            $response_time_minutes = null;
            if ($assigned_to && !$call['assigned_to']) {
                // First time assignment - calculate response time
                $reported_date = new DateTime($call['reported_date']);
                $assigned_date = new DateTime();
                $interval = $reported_date->diff($assigned_date);
                $response_time_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            }
            
            // Update maintenance call
            $sql = "UPDATE maintenance_calls SET 
                title = ?, 
                description = ?,
                call_type = ?,
                location = ?,
                province = ?,
                region = ?,
                reporter_name = ?,
                reporter_contact = ?,
                department_id = ?, 
                priority_id = ?, 
                equipment_id = ?,
                assigned_to = ?,
                status = ?,
                response_time_minutes = COALESCE(response_time_minutes, ?),
                updated_at = NOW()
                WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title,
                $description,
                $call_type,
                $location,
                $province,
                $region,
                $reporter_name,
                $reporter_contact,
                $department_id,
                $priority_id,
                $equipment_id,
                $assigned_to,
                $status,
                $response_time_minutes,
                $call_id
            ]);
            
            // Send notifications based on changes
            require_once __DIR__ . '/../includes/EmailNotificationService.php';
            $emailService = new EmailNotificationService($pdo);
            
            // If assigned to someone and assignment changed
            if ($assigned_to && $assigned_to != $call['assigned_to']) {
                $emailService->notifyAssignment($call_id, $assigned_to);
            }
            
            // If status changed to completed or resolved
            if (($status === 'completed' || $status === 'resolved') && $call['status'] !== $status) {
                $emailService->notifyCompletion($call_id);
            }
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Maintenance call updated successfully!'
            ];
            
            header('Location: view.php?id=' . $call_id);
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = "Error updating call: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<style>
.form-section {
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #1e6b3e;
}

.form-section h3 {
    color: #1e6b3e;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.form-section:first-of-type {
    margin-top: 0;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 1rem;
}

.text-danger {
    color: #dc3545 !important;
}

label {
    font-weight: 500;
    color: #495057;
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
}

.call-number {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}
</style>

<div class="page-header">
    <div>
        <h1>✏️ Edit Maintenance Call</h1>
        <p class="call-number">Call #<?php echo htmlspecialchars($call['call_number']); ?></p>
    </div>
    <div class="page-actions">
        <a href="view.php?id=<?php echo $call_id; ?>" class="btn btn-secondary">← Back to View</a>
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
            <!-- DFFE Header Information -->
            <div class="form-section">
                <h3>🏛️ DFFE Call Information</h3>
            </div>

            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="title">Call Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($call['title']); ?>" 
                           required maxlength="255">
                </div>
                
                <div class="form-group col-md-4">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="open" <?php echo $call['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="assigned" <?php echo $call['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $call['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $call['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $call['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="cancelled" <?php echo $call['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="call_type">Call Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="call_type" name="call_type" required>
                        <option value="">Select Call Type</option>
                        <option value="Plumbing" <?php echo ($call['call_type'] ?? '') === 'Plumbing' ? 'selected' : ''; ?>>Plumbing</option>
                        <option value="Electrical" <?php echo ($call['call_type'] ?? '') === 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                        <option value="Air Conditioning / HVAC" <?php echo ($call['call_type'] ?? '') === 'Air Conditioning / HVAC' ? 'selected' : ''; ?>>Air Conditioning / HVAC</option>
                        <option value="Security" <?php echo ($call['call_type'] ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                        <option value="Carpentry" <?php echo ($call['call_type'] ?? '') === 'Carpentry' ? 'selected' : ''; ?>>Carpentry</option>
                        <option value="Painting" <?php echo ($call['call_type'] ?? '') === 'Painting' ? 'selected' : ''; ?>>Painting</option>
                        <option value="Roofing" <?php echo ($call['call_type'] ?? '') === 'Roofing' ? 'selected' : ''; ?>>Roofing</option>
                        <option value="Pest Control" <?php echo ($call['call_type'] ?? '') === 'Pest Control' ? 'selected' : ''; ?>>Pest Control</option>
                        <option value="Cleaning / Sanitation" <?php echo ($call['call_type'] ?? '') === 'Cleaning / Sanitation' ? 'selected' : ''; ?>>Cleaning / Sanitation</option>
                        <option value="Grounds / Landscaping" <?php echo ($call['call_type'] ?? '') === 'Grounds / Landscaping' ? 'selected' : ''; ?>>Grounds / Landscaping</option>
                        <option value="Building Maintenance" <?php echo ($call['call_type'] ?? '') === 'Building Maintenance' ? 'selected' : ''; ?>>Building Maintenance</option>
                        <option value="IT / Technology" <?php echo ($call['call_type'] ?? '') === 'IT / Technology' ? 'selected' : ''; ?>>IT / Technology</option>
                        <option value="Other" <?php echo ($call['call_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label for="priority_id">Priority Level <span class="text-danger">*</span></label>
                    <select class="form-control" id="priority_id" name="priority_id" required>
                        <option value="">Select Priority</option>
                        <?php foreach ($priority_levels as $priority): ?>
                            <option value="<?php echo $priority['id']; ?>"
                                    <?php echo $call['priority_id'] == $priority['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($priority['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Brief Description of Issue <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" 
                          rows="5" required><?php echo htmlspecialchars($call['description']); ?></textarea>
            </div>

            <!-- Location Information -->
            <div class="form-section">
                <h3>📍 Location Details</h3>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="location">Office/Building Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($call['location']); ?>" 
                           required maxlength="255">
                </div>
                
                <div class="form-group col-md-6">
                    <label for="province">Province <span class="text-danger">*</span></label>
                    <select class="form-control" id="province" name="province" required>
                        <option value="">Select Province</option>
                        <option value="Gauteng" <?php echo ($call['province'] ?? '') === 'Gauteng' ? 'selected' : ''; ?>>Gauteng</option>
                        <option value="Western Cape" <?php echo ($call['province'] ?? '') === 'Western Cape' ? 'selected' : ''; ?>>Western Cape</option>
                        <option value="Eastern Cape" <?php echo ($call['province'] ?? '') === 'Eastern Cape' ? 'selected' : ''; ?>>Eastern Cape</option>
                        <option value="KwaZulu-Natal" <?php echo ($call['province'] ?? '') === 'KwaZulu-Natal' ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                        <option value="Mpumalanga" <?php echo ($call['province'] ?? '') === 'Mpumalanga' ? 'selected' : ''; ?>>Mpumalanga</option>
                        <option value="Limpopo" <?php echo ($call['province'] ?? '') === 'Limpopo' ? 'selected' : ''; ?>>Limpopo</option>
                        <option value="North West" <?php echo ($call['province'] ?? '') === 'North West' ? 'selected' : ''; ?>>North West</option>
                        <option value="Free State" <?php echo ($call['province'] ?? '') === 'Free State' ? 'selected' : ''; ?>>Free State</option>
                        <option value="Northern Cape" <?php echo ($call['province'] ?? '') === 'Northern Cape' ? 'selected' : ''; ?>>Northern Cape</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="region">Region <span class="text-danger">*</span></label>
                    <select class="form-control" id="region" name="region" required>
                        <option value="">Select Region</option>
                        <option value="coastal" <?php echo ($call['region'] ?? '') === 'coastal' ? 'selected' : ''; ?>>Coastal Region</option>
                        <option value="inland" <?php echo ($call['region'] ?? '') === 'inland' ? 'selected' : ''; ?>>Inland Region</option>
                    </select>
                    <small class="form-text">Coastal: WC, EC, KZN | Inland: GP, MP, LP, NW, FS, NC</small>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="department_id">Department</label>
                    <select class="form-control" id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"
                                    <?php echo $call['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Reporting Official Information -->
            <div class="form-section">
                <h3>👤 Reporting Official Details</h3>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="reporter_name">Name of Reporting Official <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reporter_name" name="reporter_name" 
                           value="<?php echo htmlspecialchars($call['reporter_name'] ?? ''); ?>" 
                           required maxlength="200">
                </div>
                
                <div class="form-group col-md-6">
                    <label for="reporter_contact">Contact Details <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reporter_contact" name="reporter_contact" 
                           value="<?php echo htmlspecialchars($call['reporter_contact'] ?? ''); ?>" 
                           required maxlength="100">
                </div>
            </div>

            <!-- Assignment Information -->
            <div class="form-section">
                <h3>🔧 Assignment & Equipment</h3>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="assigned_to">Assigned To</label>
                    <select class="form-control" id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>"
                                    <?php echo $call['assigned_to'] == $tech['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="equipment_id">Equipment (Optional)</label>
                    <select class="form-control" id="equipment_id" name="equipment_id">
                        <option value="">Select Equipment if applicable</option>
                        <?php foreach ($equipment as $equip): ?>
                            <option value="<?php echo $equip['id']; ?>"
                                    <?php echo $call['equipment_id'] == $equip['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($equip['name']); ?>
                                <?php if ($equip['asset_number']): ?>
                                    (<?php echo htmlspecialchars($equip['asset_number']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span> Save Changes
                </button>
                <a href="view.php?id=<?php echo $call_id; ?>" class="btn btn-secondary">Cancel</a>
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

.call-number {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 600;
    margin: 0;
}

.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
}

.card-body {
    padding: 24px;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.col-md-4 { flex: 0 0 33.333%; }
.col-md-6 { flex: 0 0 50%; }
.col-md-8 { flex: 0 0 66.666%; }

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

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    .col-md-4, .col-md-6, .col-md-8 {
        flex: 0 0 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
