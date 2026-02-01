<?php
require_once '../bootstrap.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

try {
    // Check if file was uploaded
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['file'];
    $entity_type = $_POST['entity_type'] ?? '';
    $entity_id = (int)($_POST['entity_id'] ?? 0);
    
    // Validate inputs
    if (!in_array($entity_type, ['maintenance_call', 'work_order'])) {
        throw new Exception('Invalid entity type');
    }
    
    if (!$entity_id) {
        throw new Exception('Invalid entity ID');
    }
    
    // Check file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    // Validate file type
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('File type not allowed. Only images, PDFs, and Office documents are permitted.');
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    
    // Create upload directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    $file_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Save to database
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO attachments (entity_type, entity_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $entity_type,
        $entity_id,
        $filename,
        $file['name'],
        'uploads/' . $filename,
        $file['size'],
        $mime_type,
        $current_user['id']
    ]);
    
    $attachment_id = $pdo->lastInsertId();
    
    // Get attachment info
    $stmt = $pdo->prepare("
        SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
        FROM attachments a
        LEFT JOIN users u ON a.uploaded_by = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'attachment' => $attachment
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
