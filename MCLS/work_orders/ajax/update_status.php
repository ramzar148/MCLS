<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $work_order_id = (int)($input['work_order_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$work_order_id || !$status) {
        throw new Exception('Invalid request');
    }
    
    // Validate status
    $valid_statuses = ['in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get work order
    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$work_order_id]);
    $wo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wo) {
        throw new Exception('Work order not found');
    }
    
    // Permission check: managers can update any, technicians only their own
    if (!$session->hasRole('manager') && $wo['assigned_to'] != $current_user['id']) {
        throw new Exception('You can only update work orders assigned to you');
    }
    
    // Build update query
    $updates = ['status = ?', 'updated_at = NOW()'];
    $params = [$status];
    
    if ($status === 'in_progress' && !$wo['actual_start']) {
        $updates[] = 'actual_start = NOW()';
    }
    
    if ($status === 'completed') {
        if (!$wo['actual_start']) {
            $updates[] = 'actual_start = NOW()';
        }
        if (!$wo['actual_end']) {
            $updates[] = 'actual_end = NOW()';
        }
    }
    
    $params[] = $work_order_id;
    
    $sql = "UPDATE work_orders SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Work order status updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
