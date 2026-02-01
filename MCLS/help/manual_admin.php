<div class="manual-section">
    <h2>⚙️ Administrator Guide</h2>
    <p>Welcome! As an administrator, you have full system access and management capabilities.</p>
</div>

<div class="quick-links">
    <a href="#users" class="quick-link">
        <div class="icon">👥</div>
        <div class="label">User Management</div>
    </a>
    <a href="#system" class="quick-link">
        <div class="icon">⚙️</div>
        <div class="label">System Settings</div>
    </a>
    <a href="#oversight" class="quick-link">
        <div class="icon">📊</div>
        <div class="label">System Oversight</div>
    </a>
    <a href="#maintenance" class="quick-link">
        <div class="icon">🔧</div>
        <div class="label">System Maintenance</div>
    </a>
</div>

<div class="manual-section" id="users">
    <h2>👥 User Management</h2>
    
    <h3>Managing User Accounts</h3>
    <ol>
        <li>Go to <strong>Admin → Users</strong></li>
        <li>View all system users with their roles and status</li>
        <li>Click on a user to edit their details</li>
    </ol>

    <h3>User Roles and Permissions</h3>
    <ul>
        <li><strong>Admin:</strong> Full system access, user management, system configuration</li>
        <li><strong>Manager:</strong> Department oversight, work assignment, work order approval</li>
        <li><strong>Technician:</strong> Accept and resolve maintenance calls, update progress</li>
        <li><strong>User:</strong> Report issues, view own calls, track status</li>
    </ul>

    <h3>Active Directory Integration</h3>
    <p>The system authenticates against Active Directory:</p>
    <ul>
        <li>Users log in with network credentials</li>
        <li>First-time login creates user record automatically</li>
        <li>Admin assigns roles and departments</li>
        <li>User details sync from AD (name, email)</li>
    </ul>

    <div class="warning">
        <strong>⚠️ Important:</strong> Always verify new users before assigning elevated roles (manager, admin).
    </div>

    <h3>Managing User Status</h3>
    <ul>
        <li><strong>Active:</strong> Can log in and use the system</li>
        <li><strong>Inactive:</strong> Cannot log in but records are preserved</li>
        <li><strong>Suspended:</strong> Temporarily disabled access</li>
    </ul>
</div>

<div class="manual-section" id="system">
    <h2>⚙️ System Configuration</h2>
    
    <h3>Department Management</h3>
    <ol>
        <li>Go to <strong>Admin → Departments</strong></li>
        <li>Add, edit, or archive departments</li>
        <li>Assign department managers</li>
        <li>Configure department-specific settings</li>
    </ol>

    <h3>Priority Levels</h3>
    <p>Configure maintenance call priority levels:</p>
    <ul>
        <li>Define priority names and colors</li>
        <li>Set response time SLAs</li>
        <li>Configure notification rules</li>
        <li>Set escalation thresholds</li>
    </ul>

    <h3>Equipment Categories</h3>
    <ol>
        <li>Go to <strong>Equipment → Categories</strong></li>
        <li>Create hierarchical equipment categories</li>
        <li>Define category-specific fields</li>
        <li>Set maintenance schedules per category</li>
    </ol>

    <h3>System Settings</h3>
    <p>Configure global system parameters:</p>
    <ul>
        <li>Email notification templates</li>
        <li>Default assignment rules</li>
        <li>Session timeout duration</li>
        <li>Password policies</li>
        <li>Backup schedules</li>
    </ul>
</div>

<div class="manual-section" id="oversight">
    <h2>📊 System-Wide Oversight</h2>
    
    <h3>Dashboard Overview</h3>
    <p>Your admin dashboard shows:</p>
    <ul>
        <li><strong>Total Calls:</strong> All maintenance calls system-wide</li>
        <li><strong>Open Calls:</strong> Active calls across all departments</li>
        <li><strong>Active Users:</strong> Current registered users</li>
        <li><strong>Equipment:</strong> Total registered equipment items</li>
    </ul>

    <h3>Cross-Department Monitoring</h3>
    <ol>
        <li>View all maintenance calls regardless of department</li>
        <li>Filter by department to review specific areas</li>
        <li>Identify bottlenecks and resource needs</li>
        <li>Monitor response times and SLA compliance</li>
    </ol>

    <h3>Audit Trail</h3>
    <p>Access comprehensive system logs:</p>
    <ul>
        <li>User login/logout activity</li>
        <li>Call creation and status changes</li>
        <li>Assignment changes</li>
        <li>Data modifications</li>
        <li>System configuration changes</li>
    </ul>

    <div class="note">
        <strong>💡 Tip:</strong> Review audit logs regularly for security and compliance purposes.
    </div>
</div>

<div class="manual-section" id="maintenance">
    <h2>🔧 System Maintenance</h2>
    
    <h3>Database Management</h3>
    <ul>
        <li>Monitor database size and performance</li>
        <li>Schedule regular backups</li>
        <li>Archive old maintenance calls</li>
        <li>Optimize database tables</li>
        <li>Review slow queries</li>
    </ul>

    <h3>Backup Procedures</h3>
    <ol>
        <li>Daily automated backups run at 2:00 AM</li>
        <li>Manual backup: Go to <strong>Admin → System → Backup</strong></li>
        <li>Backups stored in <code>/backups/</code> directory</li>
        <li>Retain backups for 30 days minimum</li>
        <li>Test restore procedures quarterly</li>
    </ol>

    <h3>Performance Monitoring</h3>
    <p>Monitor system health:</p>
    <ul>
        <li>Server CPU and memory usage</li>
        <li>Database query performance</li>
        <li>Active user sessions</li>
        <li>API response times</li>
        <li>Error log review</li>
    </ul>

    <h3>Security Management</h3>
    <ul>
        <li>Review failed login attempts</li>
        <li>Monitor suspicious activity</li>
        <li>Update security patches regularly</li>
        <li>Review user access patterns</li>
        <li>Enforce password policies</li>
    </ul>
</div>

<div class="manual-section">
    <h2>📈 Reporting and Analytics</h2>
    
    <h3>System-Wide Reports</h3>
    <p>Generate comprehensive reports:</p>
    <ul>
        <li><strong>Call Volume:</strong> Trends over time by department</li>
        <li><strong>Response Times:</strong> Average time to assignment and resolution</li>
        <li><strong>Technician Performance:</strong> Workload and completion rates</li>
        <li><strong>Equipment Reliability:</strong> Failure rates by category</li>
        <li><strong>Cost Analysis:</strong> Maintenance expenses by department</li>
    </ul>

    <h3>Custom Reports</h3>
    <ol>
        <li>Go to <strong>Admin → Reports</strong></li>
        <li>Select report type and parameters</li>
        <li>Apply filters (date range, department, priority)</li>
        <li>Generate report</li>
        <li>Export to Excel or PDF</li>
    </ol>

    <h3>Dashboard Widgets</h3>
    <p>Customize admin dashboard with widgets for:</p>
    <ul>
        <li>Real-time call statistics</li>
        <li>Department performance comparison</li>
        <li>SLA compliance metrics</li>
        <li>Recent system activity</li>
        <li>Alert notifications</li>
    </ul>
</div>

<div class="manual-section">
    <h2>🔔 Notifications and Alerts</h2>
    
    <h3>Email Notifications</h3>
    <p>Configure system notifications for:</p>
    <ul>
        <li>New call assignments</li>
        <li>Status changes</li>
        <li>SLA threshold breaches</li>
        <li>Work order approvals needed</li>
        <li>System errors or warnings</li>
    </ul>

    <h3>Escalation Rules</h3>
    <p>Set automatic escalation triggers:</p>
    <ul>
        <li>Critical calls unassigned after 15 minutes</li>
        <li>High priority calls open beyond SLA</li>
        <li>Calls in progress for extended periods</li>
        <li>Multiple failed assignments</li>
    </ul>
</div>

<div class="manual-section">
    <h2>❓ Administrator FAQs</h2>
    
    <h3>How do I add a new department?</h3>
    <p>Go to Admin → Departments → Add New. Enter department name, assign a manager, and configure settings.</p>

    <h3>What if Active Directory sync fails?</h3>
    <p>Check AD connection settings in config files. Verify LDAP credentials and test connection. Contact IT if issues persist.</p>

    <h3>How do I restore from backup?</h3>
    <p>Stop the application, restore database from backup file using MySQL tools, restart application, verify data integrity.</p>

    <h3>Can I bulk import users?</h3>
    <p>Yes, use Admin → Users → Import. Prepare CSV file with required fields (username, email, role, department) and upload.</p>

    <h3>How do I archive old data?</h3>
    <p>Go to Admin → System → Archive. Select date range for calls older than retention policy (default 2 years) and archive.</p>
</div>

<div class="manual-section">
    <h2>🚨 Troubleshooting</h2>
    
    <h3>Common Issues</h3>
    
    <h4>Users can't log in</h4>
    <ul>
        <li>Check Active Directory connectivity</li>
        <li>Verify user account is active</li>
        <li>Review error logs for authentication failures</li>
        <li>Test with local testing mode if needed</li>
    </ul>

    <h4>Slow system performance</h4>
    <ul>
        <li>Check database query performance</li>
        <li>Review server resource usage</li>
        <li>Archive old maintenance calls</li>
        <li>Optimize database indexes</li>
    </ul>

    <h4>Email notifications not sending</h4>
    <ul>
        <li>Verify SMTP settings</li>
        <li>Check email queue for errors</li>
        <li>Test email configuration</li>
        <li>Review firewall rules</li>
    </ul>
</div>

<div class="manual-section">
    <h2>📞 Support and Maintenance</h2>
    
    <h3>System Updates</h3>
    <p>Keep the system up to date:</p>
    <ul>
        <li>Review release notes before updating</li>
        <li>Backup database before major updates</li>
        <li>Test updates in staging environment first</li>
        <li>Schedule updates during low-usage periods</li>
        <li>Notify users of scheduled maintenance</li>
    </ul>

    <h3>Getting Help</h3>
    <ul>
        <li>Review system logs for error details</li>
        <li>Check documentation and FAQs</li>
        <li>Contact vendor support if needed</li>
        <li>Maintain system documentation up to date</li>
    </ul>
</div>
