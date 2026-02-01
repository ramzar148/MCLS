<?php
$page_title = 'Reports';
require_once '../bootstrap.php';
require_once '../config/database.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>📊 Reports</h1>
        <p>Generate reports and analytics for maintenance operations</p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>📈 Maintenance Call Reports</h3>
                </div>
                <div class="card-body">
                    <p>Generate reports on maintenance call activities, trends, and performance metrics.</p>
                    <ul>
                        <li><a href="maintenance_summary.php">Maintenance Summary Report</a></li>
                        <li><a href="call_status.php">Call Status Overview</a></li>
                        <li><a href="technician_performance.php">Technician Performance</a></li>
                        <li><a href="response_time.php">Response Time Analysis</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>⚙️ Equipment Reports</h3>
                </div>
                <div class="card-body">
                    <p>Analyze equipment utilization, maintenance needs, and asset management.</p>
                    <ul>
                        <li><a href="equipment_inventory.php">Equipment Inventory</a></li>
                        <li><a href="maintenance_history.php">Maintenance History</a></li>
                        <li><a href="equipment_utilization.php">Equipment Utilization</a></li>
                        <li><a href="warranty_status.php">Warranty Status</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>🏢 Department Reports</h3>
                </div>
                <div class="card-body">
                    <p>Department-specific reports and operational analytics.</p>
                    <ul>
                        <li><a href="department_overview.php">Department Overview</a></li>
                        <li><a href="budget_analysis.php">Budget Analysis</a></li>
                        <li><a href="resource_allocation.php">Resource Allocation</a></li>
                        <li><a href="user_activity.php">User Activity Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>📋 Custom Reports</h3>
                </div>
                <div class="card-body">
                    <p>Create custom reports based on specific criteria and date ranges.</p>
                    <div class="mt-3">
                        <a href="custom_report.php" class="btn btn-primary">Create Custom Report</a>
                        <a href="scheduled_reports.php" class="btn btn-secondary">Scheduled Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3>🌿 DFFE Environmental Impact Reports</h3>
        </div>
        <div class="card-body">
            <p>Specialized reports for the Department of Forestry, Fisheries and the Environment focusing on environmental compliance and sustainability metrics.</p>
            <div class="row">
                <div class="col-md-4">
                    <h5>🌳 Forestry Operations</h5>
                    <ul>
                        <li><a href="forest_maintenance.php">Forest Equipment Maintenance</a></li>
                        <li><a href="conservation_activities.php">Conservation Activities</a></li>
                        <li><a href="fire_prevention.php">Fire Prevention Equipment</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>🐟 Fisheries Management</h5>
                    <ul>
                        <li><a href="marine_equipment.php">Marine Equipment Status</a></li>
                        <li><a href="vessel_maintenance.php">Vessel Maintenance</a></li>
                        <li><a href="monitoring_systems.php">Monitoring Systems</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>🌍 Environmental Compliance</h5>
                    <ul>
                        <li><a href="environmental_monitoring.php">Environmental Monitoring</a></li>
                        <li><a href="compliance_tracking.php">Compliance Tracking</a></li>
                        <li><a href="sustainability_metrics.php">Sustainability Metrics</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>