<?php
$page_title = 'Maintenance Calls';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/MaintenanceCall.php';

$session = new SessionManager();
$session->requireAuth();

$maintenance_call = new MaintenanceCall();
$current_user = $session->getCurrentUser();

// Handle pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Handle filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority_id' => $_GET['priority'] ?? '',
    'assigned_to' => $_GET['assigned'] ?? '',
    'department_id' => $_GET['department'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '';
});

// Get maintenance calls and total count
$calls = $maintenance_call->getAll($filters, $per_page, $offset);
$total_calls = $maintenance_call->getCount($filters);
$total_pages = ceil($total_calls / $per_page);

// Get filter options
function getFilterOptions() {
    try {
        $db = new Database();
        $options = [];
        
        // Priorities
        $stmt = $db->execute("SELECT id, name, color_code FROM priority_levels ORDER BY sort_order");
        $options['priorities'] = $stmt->fetchAll();
        
        // Users (technicians and managers)
        $stmt = $db->execute("
            SELECT id, CONCAT(first_name, ' ', last_name) as name 
            FROM users 
            WHERE role IN ('technician', 'manager', 'admin') AND status = 'active'
            ORDER BY first_name, last_name
        ");
        $options['users'] = $stmt->fetchAll();
        
        // Departments
        $stmt = $db->execute("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
        $options['departments'] = $stmt->fetchAll();
        
        return $options;
        
    } catch (Exception $e) {
        error_log("Filter options error: " . $e->getMessage());
        return ['priorities' => [], 'users' => [], 'departments' => []];
    }
}

$filter_options = getFilterOptions();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">
        <span class="nav-icon" style="font-size: 1.8rem;">📋</span>
        Maintenance Calls
    </h1>
    <div class="d-flex gap-2">
        <a href="export.php?<?php echo http_build_query(array_filter($_GET)); ?>" class="btn btn-success">
            <span>📥</span> Export CSV
        </a>
        <button class="btn btn-outline" onclick="location.reload()">
            <span>🔄</span> Refresh
        </button>
        <a href="create.php" class="btn btn-primary">
            <span>➕</span> New Call
        </a>
    </div>
</div>

<!-- Statistics Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-primary mb-1"><?php echo number_format($total_calls); ?></h4>
                <p class="text-muted mb-0">Total Found</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-warning mb-1">
                    <?php echo number_format(count(array_filter($calls, fn($c) => in_array($c['status'], ['open', 'assigned', 'in_progress'])))); ?>
                </h4>
                <p class="text-muted mb-0">Open</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-danger mb-1">
                    <?php echo number_format(count(array_filter($calls, fn($c) => $c['priority_name'] === 'Critical'))); ?>
                </h4>
                <p class="text-muted mb-0">Critical</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-success mb-1">
                    <?php echo number_format(count(array_filter($calls, fn($c) => $c['assigned_to'] == $current_user['id']))); ?>
                </h4>
                <p class="text-muted mb-0">Assigned to Me</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <span>🔍</span> Filters
            <button class="btn btn-sm btn-outline float-right" onclick="clearFilters()">Clear All</button>
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-form">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="open" <?php echo ($filters['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="assigned" <?php echo ($filters['status'] ?? '') === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="on_hold" <?php echo ($filters['status'] ?? '') === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                            <option value="resolved" <?php echo ($filters['status'] ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo ($filters['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="cancelled" <?php echo ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <option value="">All Priorities</option>
                            <?php foreach ($filter_options['priorities'] as $priority): ?>
                                <option value="<?php echo $priority['id']; ?>" 
                                        <?php echo ($filters['priority_id'] ?? '') == $priority['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($priority['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned" class="form-control">
                            <option value="">All Technicians</option>
                            <option value="0" <?php echo ($filters['assigned_to'] ?? '') === '0' ? 'selected' : ''; ?>>Unassigned</option>
                            <?php foreach ($filter_options['users'] as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo ($filters['assigned_to'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-control">
                            <option value="">All Departments</option>
                            <?php foreach ($filter_options['departments'] as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($filters['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search call number, title, or description..."
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <span>🔍</span> Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <span>🗑️</span> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Calls Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Maintenance Calls 
            <?php if (!empty($filters)): ?>
                <span class="badge badge-info"><?php echo count($filters); ?> filters active</span>
            <?php endif; ?>
        </h5>
        
        <!-- Export options -->
        <div class="btn-group">
            <button class="btn btn-sm btn-outline" onclick="window.print()">
                <span>🖨️</span> Print
            </button>
            <a href="export.php?<?php echo http_build_query($filters); ?>" class="btn btn-sm btn-outline">
                <span>📊</span> Export CSV
            </a>
        </div>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($calls)): ?>
            <div class="text-center p-4">
                <div style="font-size: 3rem; opacity: 0.3;">📋</div>
                <h4 class="text-muted">No maintenance calls found</h4>
                <p class="text-muted mb-3">
                    <?php if (!empty($filters)): ?>
                        Try adjusting your filters or clearing them to see more results.
                    <?php else: ?>
                        Get started by creating your first maintenance call.
                    <?php endif; ?>
                </p>
                <a href="create.php" class="btn btn-primary">
                    <span>➕</span> Create First Call
                </a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table" id="calls-table">
                    <thead>
                        <tr>
                            <th>Call #</th>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Equipment</th>
                            <th>Reported By</th>
                            <th>Assigned To</th>
                            <th>Reported Date</th>
                            <th>Age</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calls as $call): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?php echo htmlspecialchars($call['call_number']); ?></strong>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $call['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($call['title']); ?></strong>
                                    </a>
                                    <?php if ($call['description']): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($call['description'], 0, 80)) . (strlen($call['description']) > 80 ? '...' : ''); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($call['priority_name']): ?>
                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($call['priority_color']); ?>">
                                            <?php echo htmlspecialchars($call['priority_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo match($call['status']) {
                                            'open' => 'warning',
                                            'assigned' => 'info',
                                            'in_progress' => 'primary',
                                            'on_hold' => 'secondary',
                                            'resolved' => 'success',
                                            'closed' => 'success',
                                            'cancelled' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $call['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($call['equipment_name']): ?>
                                        <strong><?php echo htmlspecialchars($call['equipment_name']); ?></strong>
                                        <?php if ($call['asset_tag']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($call['asset_tag']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No equipment</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($call['reported_by_name'] ?? 'Unknown'); ?>
                                    <?php if ($call['department_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($call['department_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($call['assigned_to_name']): ?>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($call['assigned_to_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($call['reported_date'])); ?>
                                    <br><small class="text-muted"><?php echo date('g:i A', strtotime($call['reported_date'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $days = (int)$call['days_open'];
                                    $color = $days > 7 ? 'text-danger' : ($days > 3 ? 'text-warning' : 'text-success');
                                    ?>
                                    <span class="<?php echo $color; ?>">
                                        <?php 
                                        if ($days == 0) {
                                            echo 'Today';
                                        } elseif ($days == 1) {
                                            echo '1 day';
                                        } else {
                                            echo $days . ' days';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $call['id']; ?>" 
                                           class="btn btn-outline" title="View Details">👁️</a>
                                        
                                        <?php if ($session->hasRole('technician')): ?>
                                            <a href="edit.php?id=<?php echo $call['id']; ?>" 
                                               class="btn btn-outline" title="Edit">✏️</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($call['status'] === 'open' && $session->hasRole('technician')): ?>
                                            <button onclick="assignToMe(<?php echo $call['id']; ?>)" 
                                                    class="btn btn-primary btn-sm" title="Assign to Me">👤</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($session->hasRole('admin')): ?>
                                            <button onclick="deleteCall(<?php echo $call['id']; ?>)" 
                                                    class="btn btn-danger btn-sm" title="Delete">🗑️</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_calls); ?> 
                            of <?php echo number_format($total_calls); ?> calls
                        </div>
                        
                        <div class="pagination">
                            <?php
                            $query_params = $_GET;
                            unset($query_params['page']);
                            $base_url = '?' . http_build_query($query_params) . '&page=';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="<?php echo $base_url . ($page - 1); ?>" class="btn btn-sm btn-outline">Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="<?php echo $base_url . $i; ?>" 
                                   class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo $base_url . ($page + 1); ?>" class="btn btn-sm btn-outline">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Grid System */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.col-md-3, .col-md-4, .col-md-6, .col-md-12 {
    padding: 0 10px;
    margin-bottom: 15px;
    box-sizing: border-box;
}

.col-md-3 {
    flex: 0 0 25%;
    max-width: 25%;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

.col-md-12 {
    flex: 0 0 100%;
    max-width: 100%;
}

/* Responsive breakpoints */
@media (max-width: 1024px) {
    .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 768px) {
    .col-md-3, .col-md-4, .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Smaller Stats Cards */
.card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-body {
    padding: 15px;
}

.card-body h4 {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.card-body p {
    font-size: 0.85rem;
}

.filter-form .row {
    margin-bottom: 0;
}

.pagination {
    display: flex;
    gap: 4px;
}

.pagination .btn {
    border-radius: 4px;
}

.float-right {
    float: right;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<script>
function clearFilters() {
    window.location.href = 'index.php';
}

async function assignToMe(callId) {
    if (!confirm('Assign this call to yourself?')) return;
    
    try {
        const response = await fetch('ajax/assign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.mcls.csrfToken
            },
            body: JSON.stringify({ call_id: callId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.mcls.showAlert('success', 'Call assigned successfully');
            location.reload();
        } else {
            window.mcls.showAlert('danger', result.message || 'Assignment failed');
        }
    } catch (error) {
        console.error('Assignment error:', error);
        window.mcls.showAlert('danger', 'Network error');
    }
}

async function deleteCall(callId) {
    if (!confirm('Are you sure you want to delete this maintenance call? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('ajax/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.mcls.csrfToken
            },
            body: JSON.stringify({ call_id: callId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.mcls.showAlert('success', 'Call deleted successfully');
            location.reload();
        } else {
            window.mcls.showAlert('danger', result.message || 'Deletion failed');
        }
    } catch (error) {
        console.error('Deletion error:', error);
        window.mcls.showAlert('danger', 'Network error');
    }
}

// Initialize data table features
document.addEventListener('DOMContentLoaded', function() {
    if (window.mcls) {
        window.mcls.initDataTable('#calls-table', {
            search: false, // We have custom filters
            sort: true,
            pagination: false // We have custom pagination
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>