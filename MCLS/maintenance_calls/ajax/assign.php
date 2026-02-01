<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$call_id = (int)($data['call_id'] ?? 0);
$technician_id = (int)($data['technician_id'] ?? 0);
$current_user = $session->getCurrentUser();

if (!$call_id || !$technician_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check permissions (managers and technicians can assign)
if (!$session->hasRole('manager') && !$session->hasRole('technician')) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if call exists
    $stmt = $pdo->prepare("SELECT id, status FROM maintenance_calls WHERE id = ?");
    $stmt->execute([$call_id]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        echo json_encode(['success' => false, 'message' => 'Call not found']);
        exit;
    }
    
    // Update assignment
    $stmt = $pdo->prepare("
        UPDATE maintenance_calls 
        SET assigned_to = ?, 
            status = IF(status = 'open', 'assigned', status),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$technician_id, $call_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Call assigned successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Assignment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
