<?php
$page_title = 'Create Maintenance Call';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

// Get reference data
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get departments
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get priority levels
    $stmt = $pdo->query("SELECT id, name, color_code FROM priority_levels ORDER BY level");
    $priority_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get equipment
    $stmt = $pdo->query("SELECT id, name, asset_number FROM equipment WHERE status = 'active' ORDER BY name");
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        
        // Validation
        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($description)) $errors[] = "Description is required";
        if (empty($call_type)) $errors[] = "Call Type is required";
        if (empty($location)) $errors[] = "Location is required";
        if (empty($province)) $errors[] = "Province is required";
        if (empty($region)) $errors[] = "Region is required";
        if (empty($reporter_name)) $errors[] = "Reporting Official Name is required";
        if (empty($reporter_contact)) $errors[] = "Contact Details are required";
        if (empty($priority_id)) $errors[] = "Priority is required";
        
        if (empty($errors)) {
            // Generate call number
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_calls WHERE YEAR(created_at) = ?");
            $stmt->execute([$year]);
            $count = $stmt->fetch()['count'] + 1;
            $call_number = sprintf("MC-%s-%04d", $year, $count);
            
            // Insert maintenance call
            $sql = "INSERT INTO maintenance_calls (
                call_number, title, description, call_type, location, province, region,
                reporter_name, reporter_contact, reported_by, department_id, priority_id, equipment_id,
                status, reported_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $call_number,
                $title,
                $description,
                $call_type,
                $location,
                $province,
                $region,
                $reporter_name,
                $reporter_contact,
                $current_user['id'],
                $department_id,
                $priority_id,
                $equipment_id
            ]);
            
            $call_id = $pdo->lastInsertId();
            
            // Send notifications to regional coordinators
            require_once __DIR__ . '/../includes/EmailNotificationService.php';
            $emailService = new EmailNotificationService($pdo);
            $emailService->notifyRegionalCoordinators($call_id, $region);
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Maintenance call created successfully! Call Number: ' . $call_number
            ];
            
            header('Location: index.php');
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = "Error creating call: " . $e->getMessage();
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

.form-section p {
    font-size: 0.85rem;
    margin-bottom: 0;
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
</style>

<div class="page-header">
    <div>
        <h1>📝 Create Maintenance Call</h1>
        <p>Report a new maintenance issue - DFFE Facilities Management</p>
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
                <!-- DFFE Header Information -->
                <div class="form-section">
                    <h3>🏛️ DFFE Call Information</h3>
                    <p class="text-muted">Department of Forestry, Fisheries and the Environment</p>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="title">Call Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               required maxlength="255"
                               placeholder="Brief description of the maintenance issue">
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label for="priority_id">Priority Level <span class="text-danger">*</span></label>
                        <select class="form-control" id="priority_id" name="priority_id" required>
                            <option value="">Select Priority</option>
                            <?php foreach ($priority_levels as $priority): ?>
                                <option value="<?php echo $priority['id']; ?>"
                                        <?php echo (isset($_POST['priority_id']) && $_POST['priority_id'] == $priority['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($priority['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="call_type">Call Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="call_type" name="call_type" required>
                        <option value="">Select Call Type</option>
                        <option value="Plumbing" <?php echo (($_POST['call_type'] ?? '') === 'Plumbing') ? 'selected' : ''; ?>>Plumbing</option>
                        <option value="Electrical" <?php echo (($_POST['call_type'] ?? '') === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                        <option value="Air Conditioning / HVAC" <?php echo (($_POST['call_type'] ?? '') === 'Air Conditioning / HVAC') ? 'selected' : ''; ?>>Air Conditioning / HVAC</option>
                        <option value="Security" <?php echo (($_POST['call_type'] ?? '') === 'Security') ? 'selected' : ''; ?>>Security</option>
                        <option value="Carpentry" <?php echo (($_POST['call_type'] ?? '') === 'Carpentry') ? 'selected' : ''; ?>>Carpentry</option>
                        <option value="Painting" <?php echo (($_POST['call_type'] ?? '') === 'Painting') ? 'selected' : ''; ?>>Painting</option>
                        <option value="Roofing" <?php echo (($_POST['call_type'] ?? '') === 'Roofing') ? 'selected' : ''; ?>>Roofing</option>
                        <option value="Pest Control" <?php echo (($_POST['call_type'] ?? '') === 'Pest Control') ? 'selected' : ''; ?>>Pest Control</option>
                        <option value="Cleaning / Sanitation" <?php echo (($_POST['call_type'] ?? '') === 'Cleaning / Sanitation') ? 'selected' : ''; ?>>Cleaning / Sanitation</option>
                        <option value="Grounds / Landscaping" <?php echo (($_POST['call_type'] ?? '') === 'Grounds / Landscaping') ? 'selected' : ''; ?>>Grounds / Landscaping</option>
                        <option value="Building Maintenance" <?php echo (($_POST['call_type'] ?? '') === 'Building Maintenance') ? 'selected' : ''; ?>>Building Maintenance</option>
                        <option value="IT / Technology" <?php echo (($_POST['call_type'] ?? '') === 'IT / Technology') ? 'selected' : ''; ?>>IT / Technology</option>
                        <option value="Other" <?php echo (($_POST['call_type'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Brief Description of Issue <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="5" required
                              placeholder="Provide detailed information about the maintenance issue"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Location Information -->
                <div class="form-section">
                    <h3>📍 Location Details</h3>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="location">Office/Building Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                               required maxlength="255"
                               placeholder="e.g., Environment House, Building A, Room 205">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="province">Province <span class="text-danger">*</span></label>
                        <select class="form-control" id="province" name="province" required>
                            <option value="">Select Province</option>
                            <option value="Gauteng" <?php echo (($_POST['province'] ?? '') === 'Gauteng') ? 'selected' : ''; ?>>Gauteng</option>
                            <option value="Western Cape" <?php echo (($_POST['province'] ?? '') === 'Western Cape') ? 'selected' : ''; ?>>Western Cape</option>
                            <option value="Eastern Cape" <?php echo (($_POST['province'] ?? '') === 'Eastern Cape') ? 'selected' : ''; ?>>Eastern Cape</option>
                            <option value="KwaZulu-Natal" <?php echo (($_POST['province'] ?? '') === 'KwaZulu-Natal') ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                            <option value="Mpumalanga" <?php echo (($_POST['province'] ?? '') === 'Mpumalanga') ? 'selected' : ''; ?>>Mpumalanga</option>
                            <option value="Limpopo" <?php echo (($_POST['province'] ?? '') === 'Limpopo') ? 'selected' : ''; ?>>Limpopo</option>
                            <option value="North West" <?php echo (($_POST['province'] ?? '') === 'North West') ? 'selected' : ''; ?>>North West</option>
                            <option value="Free State" <?php echo (($_POST['province'] ?? '') === 'Free State') ? 'selected' : ''; ?>>Free State</option>
                            <option value="Northern Cape" <?php echo (($_POST['province'] ?? '') === 'Northern Cape') ? 'selected' : ''; ?>>Northern Cape</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="region">Region <span class="text-danger">*</span></label>
                        <select class="form-control" id="region" name="region" required>
                            <option value="">Select Region</option>
                            <option value="coastal" <?php echo (($_POST['region'] ?? '') === 'coastal') ? 'selected' : ''; ?>>Coastal Region</option>
                            <option value="inland" <?php echo (($_POST['region'] ?? '') === 'inland') ? 'selected' : ''; ?>>Inland Region</option>
                        </select>
                        <small class="form-text">Coastal: WC, EC, KZN | Inland: GP, MP, LP, NW, FS, NC</small>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="department_id">Department</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
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
                               value="<?php echo htmlspecialchars($_POST['reporter_name'] ?? (isset($current_user['first_name']) ? $current_user['first_name'] . ' ' . ($current_user['last_name'] ?? '') : '')); ?>" 
                               required maxlength="200"
                               placeholder="Full name of person reporting issue">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="reporter_contact">Contact Details <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reporter_contact" name="reporter_contact" 
                               value="<?php echo htmlspecialchars($_POST['reporter_contact'] ?? ($current_user['email'] ?? '')); ?>" 
                               required maxlength="100"
                               placeholder="Tel: 012-399-XXXX or email@dffe.gov.za">
                    </div>
                </div>

                <div class="form-group">
                    <label for="equipment_id">Equipment (Optional)</label>
                    <select class="form-control" id="equipment_id" name="equipment_id">
                        <option value="">Select Equipment if applicable</option>
                        <?php foreach ($equipment as $equip): ?>
                            <option value="<?php echo $equip['id']; ?>"
                                    <?php echo (isset($_POST['equipment_id']) && $_POST['equipment_id'] == $equip['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($equip['name']); ?>
                                <?php if ($equip['asset_number']): ?>
                                    (<?php echo htmlspecialchars($equip['asset_number']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>📝</span> Submit Maintenance Call
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
    gap: 20px;
}

.page-header h1 {
    margin: 0 0 5px 0;
    font-size: 1.75rem;
}

.page-header p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 20px;
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

.col-md-4 {
    flex: 0 0 33.333%;
}

.col-md-6 {
    flex: 0 0 50%;
}

.col-md-8 {
    flex: 0 0 66.666%;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #495057;
}

.text-danger {
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.95rem;
    transition: border-color 0.15s;
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
