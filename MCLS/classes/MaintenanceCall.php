<?php
/**
 * Maintenance Call Class
 * Handles CRUD operations for maintenance calls
 */

if (!class_exists('MaintenanceCall')) {
class MaintenanceCall {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create a new maintenance call
     * @param array $data
     * @return int|false Call ID or false on failure
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate call number
            $call_number = $this->generateCallNumber();
            
            // Insert maintenance call
            $stmt = $this->db->execute(
                "INSERT INTO maintenance_calls (
                    call_number, title, description, equipment_id, location,
                    reported_by, assigned_to, priority_id, status, scheduled_date,
                    estimated_hours, cost
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $call_number,
                    $data['title'],
                    $data['description'],
                    $data['equipment_id'] ?: null,
                    $data['location'],
                    $data['reported_by'],
                    $data['assigned_to'] ?: null,
                    $data['priority_id'],
                    'open',
                    $data['scheduled_date'] ?: null,
                    $data['estimated_hours'] ?: null,
                    $data['cost'] ?: null
                ]
            );
            
            $call_id = $this->db->lastInsertId();
            
            // Log audit event
            $this->logAuditEvent($call_id, 'INSERT', [], $data);
            
            $this->db->commit();
            return $call_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create maintenance call error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing maintenance call
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            // Get current data for audit
            $old_data = $this->getById($id);
            if (!$old_data) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Update maintenance call
            $stmt = $this->db->execute(
                "UPDATE maintenance_calls SET 
                    title = ?, description = ?, equipment_id = ?, location = ?,
                    assigned_to = ?, priority_id = ?, status = ?, scheduled_date = ?,
                    started_date = ?, completed_date = ?, estimated_hours = ?,
                    actual_hours = ?, cost = ?, resolution_notes = ?,
                    updated_at = NOW()
                WHERE id = ?",
                [
                    $data['title'],
                    $data['description'],
                    $data['equipment_id'] ?: null,
                    $data['location'],
                    $data['assigned_to'] ?: null,
                    $data['priority_id'],
                    $data['status'],
                    $data['scheduled_date'] ?: null,
                    $data['started_date'] ?: null,
                    $data['completed_date'] ?: null,
                    $data['estimated_hours'] ?: null,
                    $data['actual_hours'] ?: null,
                    $data['cost'] ?: null,
                    $data['resolution_notes'] ?: null,
                    $id
                ]
            );
            
            // Log audit event
            $this->logAuditEvent($id, 'UPDATE', $old_data, $data);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update maintenance call error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get maintenance call by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $stmt = $this->db->execute(
                "SELECT 
                    mc.*,
                    pl.name as priority_name,
                    pl.color_code as priority_color,
                    CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
                    e.name as equipment_name,
                    e.asset_tag,
                    d.name as department_name
                FROM maintenance_calls mc
                LEFT JOIN priority_levels pl ON mc.priority_id = pl.id
                LEFT JOIN users u1 ON mc.reported_by = u1.id
                LEFT JOIN users u2 ON mc.assigned_to = u2.id
                LEFT JOIN equipment e ON mc.equipment_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE mc.id = ?",
                [$id]
            );
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get maintenance call error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all maintenance calls with filters
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($filters = [], $limit = 50, $offset = 0) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Build WHERE clause based on filters
            if (!empty($filters['status'])) {
                $where_conditions[] = "mc.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority_id'])) {
                $where_conditions[] = "mc.priority_id = ?";
                $params[] = $filters['priority_id'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $where_conditions[] = "mc.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (!empty($filters['department_id'])) {
                $where_conditions[] = "e.department_id = ?";
                $params[] = $filters['department_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(mc.reported_date) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(mc.reported_date) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(mc.call_number LIKE ? OR mc.title LIKE ? OR mc.description LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
            
            $query = "
                SELECT 
                    mc.*,
                    pl.name as priority_name,
                    pl.color_code as priority_color,
                    CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
                    e.name as equipment_name,
                    e.asset_number,
                    d.name as department_name,
                    DATEDIFF(NOW(), mc.reported_date) as days_open
                FROM maintenance_calls mc
                LEFT JOIN priority_levels pl ON mc.priority_id = pl.id
                LEFT JOIN users u1 ON mc.reported_by = u1.id
                LEFT JOIN users u2 ON mc.assigned_to = u2.id
                LEFT JOIN equipment e ON mc.equipment_id = e.id
                LEFT JOIN departments d ON mc.department_id = d.id
                {$where_clause}
                ORDER BY mc.reported_date DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->execute($query, $params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get all maintenance calls error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get count of maintenance calls with filters
     * @param array $filters
     * @return int
     */
    public function getCount($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Apply same filters as getAll()
            if (!empty($filters['status'])) {
                $where_conditions[] = "mc.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority_id'])) {
                $where_conditions[] = "mc.priority_id = ?";
                $params[] = $filters['priority_id'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $where_conditions[] = "mc.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (!empty($filters['department_id'])) {
                $where_conditions[] = "mc.department_id = ?";
                $params[] = $filters['department_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(mc.reported_date) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(mc.reported_date) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(mc.call_number LIKE ? OR mc.title LIKE ? OR mc.description LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
            
            $query = "
                SELECT COUNT(*) as total
                FROM maintenance_calls mc
                LEFT JOIN equipment e ON mc.equipment_id = e.id
                {$where_clause}
            ";
            
            $stmt = $this->db->execute($query, $params);
            $result = $stmt->fetch();
            
            return (int)$result['total'];
            
        } catch (Exception $e) {
            error_log("Get maintenance call count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete a maintenance call
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            // Get current data for audit
            $old_data = $this->getById($id);
            if (!$old_data) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Delete maintenance call (CASCADE will handle related records)
            $stmt = $this->db->execute("DELETE FROM maintenance_calls WHERE id = ?", [$id]);
            
            // Log audit event
            $this->logAuditEvent($id, 'DELETE', $old_data, []);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Delete maintenance call error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique call number
     * @return string
     */
    private function generateCallNumber() {
        $prefix = 'MC';
        $year = date('Y');
        $month = date('m');
        
        try {
            // Get next sequence number for this month
            $stmt = $this->db->execute(
                "SELECT COUNT(*) + 1 as next_seq 
                FROM maintenance_calls 
                WHERE call_number LIKE ?",
                [$prefix . $year . $month . '%']
            );
            
            $result = $stmt->fetch();
            $sequence = str_pad($result['next_seq'], 4, '0', STR_PAD_LEFT);
            
            return $prefix . $year . $month . $sequence;
            
        } catch (Exception $e) {
            error_log("Generate call number error: " . $e->getMessage());
            return $prefix . $year . $month . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Update call status
     * @param int $id
     * @param string $status
     * @param array $additional_data
     * @return bool
     */
    public function updateStatus($id, $status, $additional_data = []) {
        try {
            $update_fields = ['status = ?'];
            $params = [$status];
            
            // Add timestamp fields based on status
            switch ($status) {
                case 'in_progress':
                    if (empty($additional_data['started_date'])) {
                        $update_fields[] = 'started_date = NOW()';
                    }
                    break;
                    
                case 'resolved':
                case 'closed':
                    if (empty($additional_data['completed_date'])) {
                        $update_fields[] = 'completed_date = NOW()';
                    }
                    break;
            }
            
            // Add any additional fields
            foreach ($additional_data as $field => $value) {
                if (in_array($field, ['started_date', 'completed_date', 'resolution_notes', 'actual_hours'])) {
                    $update_fields[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            $params[] = $id;
            
            $stmt = $this->db->execute(
                "UPDATE maintenance_calls SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = ?",
                $params
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Update call status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign call to technician
     * @param int $id
     * @param int $technician_id
     * @return bool
     */
    public function assign($id, $technician_id) {
        try {
            $stmt = $this->db->execute(
                "UPDATE maintenance_calls SET 
                    assigned_to = ?, 
                    status = CASE WHEN status = 'open' THEN 'assigned' ELSE status END,
                    updated_at = NOW()
                WHERE id = ?",
                [$technician_id, $id]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Assign call error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add comment to maintenance call
     * @param int $call_id
     * @param int $user_id
     * @param string $comment
     * @param bool $is_internal
     * @return bool
     */
    public function addComment($call_id, $user_id, $comment, $is_internal = false) {
        try {
            $stmt = $this->db->execute(
                "INSERT INTO call_comments (maintenance_call_id, user_id, comment, is_internal) 
                VALUES (?, ?, ?, ?)",
                [$call_id, $user_id, $comment, $is_internal ? 1 : 0]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Add comment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get comments for maintenance call
     * @param int $call_id
     * @param bool $include_internal
     * @return array
     */
    public function getComments($call_id, $include_internal = true) {
        try {
            $where_clause = $include_internal ? '' : 'AND c.is_internal = 0';
            
            $stmt = $this->db->execute(
                "SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.role as user_role
                FROM call_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.maintenance_call_id = ? {$where_clause}
                ORDER BY c.created_at DESC",
                [$call_id]
            );
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get comments error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log audit event
     * @param int $record_id
     * @param string $action
     * @param array $old_values
     * @param array $new_values
     */
    private function logAuditEvent($record_id, $action, $old_values, $new_values) {
        try {
            $this->db->execute(
                "INSERT INTO audit_log (
                    user_id, table_name, record_id, action, 
                    old_values, new_values, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $_SESSION['user_id'] ?? null,
                    'maintenance_calls',
                    $record_id,
                    $action,
                    json_encode($old_values),
                    json_encode($new_values),
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Get maintenance call statistics
     * @return array
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total calls
            $stmt = $this->db->execute("SELECT COUNT(*) as total FROM maintenance_calls");
            $stats['total'] = $stmt->fetch()['total'];
            
            // Calls by status
            $stmt = $this->db->execute("
                SELECT status, COUNT(*) as count 
                FROM maintenance_calls 
                GROUP BY status
            ");
            $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $stats['by_status'] = $status_counts;
            
            // Calls by priority
            $stmt = $this->db->execute("
                SELECT pl.name, COUNT(*) as count 
                FROM maintenance_calls mc
                JOIN priority_levels pl ON mc.priority_id = pl.id
                WHERE mc.status NOT IN ('resolved', 'closed', 'cancelled')
                GROUP BY pl.id, pl.name
                ORDER BY pl.sort_order
            ");
            $stats['by_priority'] = $stmt->fetchAll();
            
            // Average resolution time
            $stmt = $this->db->execute("
                SELECT AVG(TIMESTAMPDIFF(HOUR, reported_date, completed_date)) as avg_hours
                FROM maintenance_calls 
                WHERE status IN ('resolved', 'closed') 
                AND completed_date IS NOT NULL
                AND reported_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $result = $stmt->fetch();
            $stats['avg_resolution_hours'] = round($result['avg_hours'] ?? 0, 1);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [];
        }
    }
}
} // End class_exists check
?>