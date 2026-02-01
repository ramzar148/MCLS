<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

// Only admins can toggle department status
if (!$session->hasRole('admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only administrators can update department status'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $department_id = (int)($input['department_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$department_id || !in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid request');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update department status
    $stmt = $pdo->prepare("UPDATE departments SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $department_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Department status updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
