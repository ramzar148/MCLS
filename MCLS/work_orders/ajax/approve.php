<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

// Only managers can approve
if (!$session->hasRole('manager')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only managers can approve work orders'
    ]);
    exit;
}

$current_user = $session->getCurrentUser();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $work_order_id = (int)($input['work_order_id'] ?? 0);
    
    if (!$work_order_id) {
        throw new Exception('Invalid work order ID');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verify work order exists and is pending
    $stmt = $pdo->prepare("SELECT id, status FROM work_orders WHERE id = ?");
    $stmt->execute([$work_order_id]);
    $wo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wo) {
        throw new Exception('Work order not found');
    }
    
    if ($wo['status'] !== 'pending') {
        throw new Exception('Only pending work orders can be approved');
    }
    
    // Update work order
    $stmt = $pdo->prepare("
        UPDATE work_orders 
        SET status = 'approved',
            approved_by = ?,
            approved_date = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$current_user['id'], $work_order_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Work order approved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
