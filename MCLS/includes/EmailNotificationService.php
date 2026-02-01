<?php
/**
 * Email Notification Service
 * DFFE Requirement: Email notifications for regional coordinators and reporting officials
 */

class EmailNotificationService {
    private $pdo;
    private $from_email = 'noreply@dffe.gov.za';
    private $from_name = 'DFFE Maintenance System';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send notification to regional coordinators when new call is logged
     */
    public function notifyRegionalCoordinators($call_id, $region) {
        try {
            // Get call details
            $stmt = $this->pdo->prepare("
                SELECT mc.*, u.first_name, u.last_name, u.email as reporter_email,
                       p.name as priority_name
                FROM maintenance_calls mc
                LEFT JOIN users u ON mc.reported_by = u.id
                LEFT JOIN priority_levels p ON mc.priority_id = p.id
                WHERE mc.id = ?
            ");
            $stmt->execute([$call_id]);
            $call = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$call) {
                throw new Exception("Call not found");
            }
            
            // Get regional coordinators for this region
            $stmt = $this->pdo->prepare("
                SELECT * FROM regional_coordinators 
                WHERE region = ? AND status = 'active'
            ");
            $stmt->execute([$region]);
            $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($coordinators)) {
                error_log("No active coordinators found for region: $region");
                return false;
            }
            
            $subject = "New Maintenance Call - {$call['call_number']} - {$call['province']}";
            $message = $this->buildNewCallEmailTemplate($call);
            
            // Send to all coordinators in this region
            foreach ($coordinators as $coordinator) {
                $sent = $this->sendEmail(
                    $coordinator['email'],
                    $coordinator['name'],
                    $subject,
                    $message
                );
                
                // Log notification
                $this->logNotification(
                    $call_id,
                    'new_call',
                    'coordinator',
                    $coordinator['email'],
                    $coordinator['name'],
                    $subject,
                    $message,
                    $sent ? 'sent' : 'failed'
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error notifying regional coordinators: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when call is assigned to technician
     */
    public function notifyAssignment($call_id, $technician_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT mc.*, u.first_name, u.last_name, u.email,
                       reporter.email as reporter_email, reporter.first_name as reporter_fname
                FROM maintenance_calls mc
                LEFT JOIN users u ON mc.assigned_to = u.id
                LEFT JOIN users reporter ON mc.reported_by = reporter.id
                WHERE mc.id = ?
            ");
            $stmt->execute([$call_id]);
            $call = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$call || !$call['email']) {
                return false;
            }
            
            $subject = "Maintenance Call Assigned - {$call['call_number']}";
            $message = $this->buildAssignmentEmailTemplate($call);
            
            $sent = $this->sendEmail(
                $call['email'],
                $call['first_name'] . ' ' . $call['last_name'],
                $subject,
                $message
            );
            
            $this->logNotification(
                $call_id,
                'assignment',
                'technician',
                $call['email'],
                $call['first_name'] . ' ' . $call['last_name'],
                $subject,
                $message,
                $sent ? 'sent' : 'failed'
            );
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Error notifying assignment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send completion confirmation to reporting official
     */
    public function notifyCompletion($call_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT mc.*, u.first_name, u.last_name,
                       tech.first_name as tech_fname, tech.last_name as tech_lname
                FROM maintenance_calls mc
                LEFT JOIN users u ON mc.reported_by = u.id
                LEFT JOIN users tech ON mc.assigned_to = tech.id
                WHERE mc.id = ?
            ");
            $stmt->execute([$call_id]);
            $call = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$call) {
                return false;
            }
            
            // Send to reporting official's contact
            $recipient_email = $call['reporter_contact'];
            $recipient_name = $call['reporter_name'];
            
            // Validate email format
            if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid reporter email: $recipient_email");
                return false;
            }
            
            $subject = "Maintenance Call Completed - {$call['call_number']}";
            $message = $this->buildCompletionEmailTemplate($call);
            
            $sent = $this->sendEmail(
                $recipient_email,
                $recipient_name,
                $subject,
                $message
            );
            
            $this->logNotification(
                $call_id,
                'completion',
                'reporter',
                $recipient_email,
                $recipient_name,
                $subject,
                $message,
                $sent ? 'sent' : 'failed'
            );
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Error notifying completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build email template for new call notification
     */
    private function buildNewCallEmailTemplate($call) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #1e6b3e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .call-info { background-color: #f8f9fa; border-left: 4px solid #1e6b3e; padding: 15px; margin: 15px 0; }
                .call-info strong { color: #1e6b3e; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .priority-high { color: #dc3545; font-weight: bold; }
                .priority-medium { color: #ffc107; font-weight: bold; }
                .priority-low { color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🏛️ Department of Forestry, Fisheries and the Environment</h2>
                <h3>New Maintenance Call Notification</h3>
            </div>
            <div class='content'>
                <p>Dear Regional Coordinator,</p>
                <p>A new maintenance call has been logged in your region:</p>
                
                <div class='call-info'>
                    <p><strong>Call Number:</strong> {$call['call_number']}</p>
                    <p><strong>Call Type:</strong> {$call['call_type']}</p>
                    <p><strong>Title:</strong> {$call['title']}</p>
                    <p><strong>Priority:</strong> <span class='priority-" . strtolower($call['priority_name']) . "'>{$call['priority_name']}</span></p>
                    <p><strong>Location:</strong> {$call['location']}</p>
                    <p><strong>Province:</strong> {$call['province']}</p>
                    <p><strong>Region:</strong> " . ucfirst($call['region']) . "</p>
                    <p><strong>Reporting Official:</strong> {$call['reporter_name']}</p>
                    <p><strong>Contact:</strong> {$call['reporter_contact']}</p>
                    <p><strong>Date Reported:</strong> " . date('d M Y H:i', strtotime($call['reported_date'])) . "</p>
                </div>
                
                <p><strong>Description:</strong></p>
                <p>{$call['description']}</p>
                
                <p>Please log in to the Maintenance Call Logging System to view full details and take necessary action.</p>
                
                <p style='margin-top: 20px;'>
                    <a href='http://localhost/MCLS/regional_dashboard.php' style='background-color: #1e6b3e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>View Regional Dashboard</a>
                    <a href='http://localhost/MCLS/maintenance_calls/view.php?id={$call['id']}' style='background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Call Details</a>
                </p>
            </div>
            <div class='footer'>
                <p>This is an automated notification from the DFFE Maintenance Call Logging System.</p>
                <p>Do not reply to this email.</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Build email template for assignment notification
     */
    private function buildAssignmentEmailTemplate($call) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #1e6b3e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .call-info { background-color: #f8f9fa; border-left: 4px solid #1e6b3e; padding: 15px; margin: 15px 0; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🏛️ DFFE Maintenance System</h2>
                <h3>Maintenance Call Assigned to You</h3>
            </div>
            <div class='content'>
                <p>Dear {$call['first_name']},</p>
                <p>A maintenance call has been assigned to you:</p>
                
                <div class='call-info'>
                    <p><strong>Call Number:</strong> {$call['call_number']}</p>
                    <p><strong>Call Type:</strong> {$call['call_type']}</p>
                    <p><strong>Title:</strong> {$call['title']}</p>
                    <p><strong>Location:</strong> {$call['location']}, {$call['province']}</p>
                    <p><strong>Reporter:</strong> {$call['reporter_name']} ({$call['reporter_contact']})</p>
                </div>
                
                <p><strong>Description:</strong></p>
                <p>{$call['description']}</p>
                
                <p>Please log in to the system to accept and begin work on this call.</p>
                
                <p><a href='http://localhost/MCLS/maintenance_calls/view.php?id={$call['id']}' style='background-color: #1e6b3e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Call Details</a></p>
            </div>
            <div class='footer'>
                <p>DFFE Maintenance Call Logging System</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Build email template for completion notification
     */
    private function buildCompletionEmailTemplate($call) {
        $completion_time = $call['resolved_at'] ?? date('Y-m-d H:i:s');
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #1e6b3e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .call-info { background-color: #e8f5e9; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .success { color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🏛️ Department of Forestry, Fisheries and the Environment</h2>
                <h3>✅ Maintenance Call Completed</h3>
            </div>
            <div class='content'>
                <p>Dear {$call['reporter_name']},</p>
                <p>This is to confirm that the maintenance call you reported has been <span class='success'>COMPLETED</span>.</p>
                
                <div class='call-info'>
                    <p><strong>Call Number:</strong> {$call['call_number']}</p>
                    <p><strong>Call Type:</strong> {$call['call_type']}</p>
                    <p><strong>Title:</strong> {$call['title']}</p>
                    <p><strong>Location:</strong> {$call['location']}, {$call['province']}</p>
                    <p><strong>Reported Date:</strong> " . date('d M Y H:i', strtotime($call['reported_date'])) . "</p>
                    <p><strong>Completion Date:</strong> " . date('d M Y H:i', strtotime($completion_time)) . "</p>
                    <p><strong>Technician:</strong> {$call['tech_fname']} {$call['tech_lname']}</p>
                </div>
                
                <p><strong>Original Issue:</strong></p>
                <p>{$call['description']}</p>
                
                <p>If you have any questions or concerns about the work completed, please contact the Facilities Management office.</p>
                
                <p>Thank you for reporting this issue and helping us maintain our facilities.</p>
            </div>
            <div class='footer'>
                <p>Department of Forestry, Fisheries and the Environment</p>
                <p>Facilities Management Division</p>
                <p>This is an automated notification. Do not reply to this email.</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Send email using PHP mail function
     */
    private function sendEmail($to_email, $to_name, $subject, $html_message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$this->from_name} <{$this->from_email}>",
            "Reply-To: {$this->from_email}",
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // In development, log instead of sending
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            error_log("EMAIL NOTIFICATION (DEV MODE):");
            error_log("To: $to_email ($to_name)");
            error_log("Subject: $subject");
            error_log("Message: " . strip_tags($html_message));
            return true;
        }
        
        // Send actual email in production
        return mail($to_email, $subject, $html_message, implode("\r\n", $headers));
    }
    
    /**
     * Log notification in database
     */
    private function logNotification($call_id, $type, $recipient_type, $email, $name, $subject, $message, $status = 'sent', $error = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_log 
                (maintenance_call_id, notification_type, recipient_type, recipient_email, 
                 recipient_name, subject, message, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $call_id,
                $type,
                $recipient_type,
                $email,
                $name,
                $subject,
                $message,
                $status,
                $error
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return false;
        }
    }
}
