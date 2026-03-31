<?php
/**
 * WhatsApp Integration System
 * SDW SaaS - Auto-send status updates via WhatsApp
 */

/**
 * Send WhatsApp message using click-to-chat link
 * Returns WhatsApp URL for redirect or button
 */
function getWhatsAppLink($phone, $message) {
    // Remove any non-numeric characters except +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // If phone starts with 0, replace with 91 (India code)
    if (strpos($phone, '0') === 0) {
        $phone = '91' . substr($phone, 1);
    }
    
    // If no country code, add 91
    if (strpos($phone, '+') !== 0 && strlen($phone) === 10) {
        $phone = '91' . $phone;
    }
    
    $encodedMessage = urlencode($message);
    return "https://wa.me/{$phone}?text={$encodedMessage}";
}

/**
 * Generate status update message
 */
function getStatusUpdateMessage($requestId, $status, $serviceName) {
    $messages = [
        'Pending' => "Hello! 👋\n\nYour application for {$serviceName} has been received successfully.\n\n📋 Application ID: #{$requestId}\n✅ Status: Pending\n\nOur team will review your application shortly. Thank you for choosing " . SITE_NAME . "!",
        
        'Processing' => "Great News! 🎉\n\nYour {$serviceName} application is now being processed.\n\n📋 Application ID: #{$requestId}\n⚙️ Status: Processing\n\nWe're working on your request. You'll be notified once it's completed.\n\nThank you for your patience!",
        
        'Completed' => "Congratulations! 🎊\n\nYour {$serviceName} application has been completed successfully!\n\n📋 Application ID: #{$requestId}\n✅ Status: Completed\n\nYou can download your documents from your dashboard.\n\nThank you for choosing " . SITE_NAME . "!",
        
        'Rejected' => "Application Update\n\nYour {$serviceName} application has been rejected.\n\n📋 Application ID: #{$requestId}\n❌ Status: Rejected\n\nPlease contact support for more details or reapply with correct information.\n\nSupport: +91-XXX-XXX-XXXX"
    ];
    
    return $messages[$status] ?? "Status Updated\n\nApplication ID: #{$requestId}\nNew Status: {$status}";
}

/**
 * Send welcome message to new user
 */
function getWelcomeMessage($userName, $email, $phone) {
    return "Welcome to " . SITE_NAME . "! 🎉\n\nHello {$userName},\n\nThank you for registering with us!\n\n📧 Email: {$email}\n📱 Phone: {$phone}\n\nYou can now apply for various digital services through our platform.\n\nNeed help? Contact us at support@sansdigitalwork.com\n\nHappy Shopping! 😊";
}

/**
 * Send payment confirmation message
 */
function getPaymentConfirmationMessage($requestId, $amount, $serviceName) {
    return "Payment Received! ✅\n\nThank you for your payment.\n\n📋 Application ID: #{$requestId}\n💰 Amount: ₹" . number_format($amount, 2) . "\n📄 Service: {$serviceName}\n\nYour application is now confirmed. We'll process it shortly.\n\nTransaction ID: PAY" . time() . "\n\nThank you for your business!";
}

/**
 * Display WhatsApp button for manual sending
 */
function displayWhatsAppButton($phone, $message, $buttonText = 'Send via WhatsApp', $class = 'btn btn-success') {
    $link = getWhatsAppLink($phone, $message);
    return '<a href="' . htmlspecialchars($link) . '" target="_blank" class="' . htmlspecialchars($class) . '">
                <i class="bi bi-whatsapp"></i> ' . htmlspecialchars($buttonText) . '
            </a>';
}

/**
 * Auto-send on status update (if API available)
 * This is a placeholder for actual API integration
 */
function autoSendWhatsApp($phone, $message) {
    // For now, just log the message
    // In production, integrate with WhatsApp Business API or services like:
    // - Twilio WhatsApp API
    // - Gupshup
    // - Msg91
    // - WATI
    
    error_log("WhatsApp Message to {$phone}: {$message}");
    
    return [
        'success' => true,
        'message' => 'Message logged (API not configured)',
        'manual_link' => getWhatsAppLink($phone, $message)
    ];
}

?>
