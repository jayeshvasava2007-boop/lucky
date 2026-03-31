<?php
/**
 * Email Notification System
 * Sans Digital Work - SDW
 * Send automated emails for various events
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Send application status update email
 */
function sendStatusUpdateEmail($userId, $applicationId, $newStatus, $remarks = '') {
    $db = Database::getInstance()->getConnection();
    
    // Get user details
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Get application details
    $stmt = $db->prepare("
        SELECT sr.*, s.service_name 
        FROM service_requests sr 
        JOIN servicesand s ON sr.service_id = s.id 
        WHERE sr.id = ?
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    if (!$application) {
        return false;
    }
    
    // Email subject based on status
    $subject = match($newStatus) {
        'Processing' => "Your Application #{$applicationId} is Being Processed",
        'Completed' => "Great News! Your Application #{$applicationId} is Complete",
        'Rejected' => "Update on Your Application #{$applicationId}",
        default => "Application Status Update #{$applicationId}"
    };
    
    // Email body
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
            .status-processing { background: #17a2b8; color: white; }
            .status-completed { background: #28a745; color: white; }
            .status-rejected { background: #dc3545; color: white; }
            .status-pending { background: #ffc107; color: #000; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 " + SITE_NAME . "</h1>
                <p>Your Trusted Digital Service Partner</p>
            </div>
            <div class='content'>
                <h2>Dear " . htmlspecialchars($user['name']) . ",</h2>
                
                <p>We're writing to inform you about an update to your application:</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Application ID:</strong> #{$applicationId}</p>
                    <p><strong>Service:</strong> " . htmlspecialchars($application['service_name']) . "</p>
                    <p><strong>Current Status:</strong></p>
                    <span class='status-badge status-" . strtolower($newStatus) . "'>" . htmlspecialchars($newStatus) . "</span>
                    
                    " . ($remarks ? "<p><strong>Remarks:</strong> " . htmlspecialchars($remarks) . "</p>" : "") . "
                    
                    <p><strong>Applied On:</strong> " . date('d M Y, h:i A', strtotime($application['created_at'])) . "</p>
                </div>
                
                <p>Our team is working on your application. You can check the current status anytime by logging into your account.</p>
                
                <div style='text-align: center;'>
                    <a href='" . SITE_URL . "/my-applications.php' class='btn'>View My Applications</a>
                </div>
                
                <p style='margin-top: 25px;'>If you have any questions, feel free to contact us:</p>
                <ul>
                    <li>📞 Phone: +91 9876543210</li>
                    <li>💬 WhatsApp: <a href='https://wa.me/919876543210'>Click to Chat</a></li>
                    <li>📧 Email: support@sansdigitalwork.com</li>
                </ul>
            </div>
            <div class='footer'>
                <p><strong>" . SITE_NAME . "</strong></p>
                <p>Your trusted partner for digital services</p>
                <p style='font-size: 12px; color: #999;'>
                    ⚠️ Please note: All fees are non-refundable once the application process begins.<br>
                    This is an automated message. Please do not reply directly.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <noreply@sansdigitalwork.com>',
        'Reply-To: support@sansdigitalwork.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email
    return mail($user['email'], $subject, $message, implode("\r\n", $headers));
}

/**
 * Send welcome email after registration
 */
function sendWelcomeEmail($userId) {
    $db = Database::getInstance()->getConnection();
    
    // Get user details
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    $subject = "Welcome to " . SITE_NAME . "! 🎉";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .features { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .feature-item { padding: 10px 0; border-bottom: 1px solid #eee; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to " . SITE_NAME . "!</h1>
                <p>Your Digital Service Partner</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($user['name']) . ",</h2>
                
                <p>Thank you for registering with us! We're excited to serve you.</p>
                
                <div class='features'>
                    <h3>Our Services Include:</h3>
                    <div class='feature-item'>✅ PAN Card Services (New & Changes)</div>
                    <div class='feature-item'>✅ Aadhar Card Updates & New Registration</div>
                    <div class='feature-item'>✅ Voter ID Card Services</div>
                    <div class='feature-item'>✅ Driving License Assistance</div>
                    <div class='feature-item'>✅ Bank & Financial Services</div>
                    <div class='feature-item'>✅ Job Placement & Online Work</div>
                    <div class='feature-item'>✅ School & College Admission Support</div>
                </div>
                
                <p>You can now apply for services online and track your applications in real-time.</p>
                
                <div style='text-align: center;'>
                    <a href='" . SITE_URL . "/dashboard.php' class='btn'>Go to Dashboard</a>
                </div>
                
                <p style='margin-top: 25px;'><strong>Need Help?</strong></p>
                <ul>
                    <li>📞 Call us: +91 9876543210</li>
                    <li>💬 WhatsApp: <a href='https://wa.me/919876543210'>Chat with Us</a></li>
                    <li>📧 Email: support@sansdigitalwork.com</li>
                </ul>
            </div>
            <div class='footer'>
                <p><strong>" . SITE_NAME . "</strong></p>
                <p>Making digital services simple and accessible</p>
                <p style='font-size: 12px; color: #999;'>
                    ⚠️ Important: All service fees are non-refundable once processing begins.<br>
                    By using our services, you agree to our Terms & Conditions.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <welcome@sansdigitalwork.com>',
        'Reply-To: support@sansdigitalwork.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($user['email'], $subject, $message, implode("\r\n", $headers));
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmation($userId, $applicationId, $amount, $paymentId = '') {
    $db = Database::getInstance()->getConnection();
    
    // Get user details
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    $subject = "Payment Confirmation - Application #{$applicationId}";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .success-icon { font-size: 48px; margin-bottom: 10px; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .payment-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745; }
            .amount { font-size: 32px; font-weight: bold; color: #28a745; margin: 10px 0; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='success-icon'>✓</div>
                <h1>Payment Successful!</h1>
                <p>Your payment has been received</p>
            </div>
            <div class='content'>
                <h2>Dear " . htmlspecialchars($user['name']) . ",</h2>
                
                <p>We're pleased to confirm that your payment has been successfully processed.</p>
                
                <div class='payment-box'>
                    <p><strong>Application ID:</strong> #{$applicationId}</p>
                    <p><strong>Amount Paid:</strong></p>
                    <div class='amount'>₹" . number_format($amount, 2) . "</div>
                    " . ($paymentId ? "<p><strong>Transaction ID:</strong> {$paymentId}</p>" : "") . "
                    <p><strong>Payment Date:</strong> " . date('d M Y, h:i A') . "</p>
                </div>
                
                <p>Your application will now be processed. You'll receive updates via email as we progress.</p>
                
                <p style='margin-top: 25px;'><strong>Next Steps:</strong></p>
                <ol>
                    <li>Our team will review your application</li>
                    <li>You'll receive status updates via email</li>
                    <li>Track your application anytime from your dashboard</li>
                </ol>
                
                <p style='margin-top: 25px;'><strong>Questions or Concerns?</strong></p>
                <ul>
                    <li>📞 Call: +91 9876543210</li>
                    <li>💬 WhatsApp: <a href='https://wa.me/919876543210'>Chat with Us</a></li>
                    <li>📧 Email: support@sansdigitalwork.com</li>
                </ul>
            </div>
            <div class='footer'>
                <p><strong>" . SITE_NAME . "</strong></p>
                <p>Thank you for choosing our services</p>
                <p style='font-size: 12px; color: #999;'>
                    ⚠️ Note: Fees are non-refundable as per our Terms & Conditions.<br>
                    This is a system-generated message.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <payments@sansdigitalwork.com>',
        'Reply-To: support@sansdigitalwork.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($user['email'], $subject, $message, implode("\r\n", $headers));
}

?>
