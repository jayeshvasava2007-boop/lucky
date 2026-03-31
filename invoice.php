<?php
/**
 * PDF Invoice Generation
 * SDW SaaS - Auto-generate invoices and receipts
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Generate PDF Invoice for service request
 */
function generateInvoicePDF($requestId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Get request details
        $stmt = $db->prepare("
            SELECT 
                sr.*,
                u.name AS user_name,
                u.email AS user_email,
                u.phone AS user_phone,
                s.service_name,
                s.description AS service_description,
                a.full_name AS processed_by
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            JOIN servicesand s ON sr.service_id = s.id
            LEFT JOIN admins a ON sr.admin_id = a.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        // Create invoice directory if not exists
        $invoiceDir = __DIR__ . '/../invoices/';
        if (!file_exists($invoiceDir)) {
            mkdir($invoiceDir, 0755, true);
        }
        
        // Generate invoice filename
        $invoiceFilename = 'invoice_' . $requestId . '_' . time() . '.pdf';
        $invoicePath = $invoiceDir . $invoiceFilename;
        
        // Generate HTML content
        $html = generateInvoiceHTML($request);
        
        // Save PDF (using simple text-based PDF for now)
        // In production, use TCPDF or Dompdf library
        saveSimplePDF($invoicePath, $html);
        
        // Update database
        $stmt = $db->prepare("
            UPDATE service_requests 
            SET invoice_generated = TRUE, invoice_path = ?
            WHERE id = ?
        ");
        $stmt->execute([$invoiceFilename, $requestId]);
        
        return [
            'success' => true,
            'message' => 'Invoice generated successfully',
            'invoice_path' => $invoiceFilename,
            'invoice_url' => SITE_URL . '/invoices/' . $invoiceFilename
        ];
        
    } catch (Exception $e) {
        error_log("Generate invoice error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to generate invoice'
        ];
    }
}

/**
 * Generate HTML content for invoice
 */
function generateInvoiceHTML($request) {
    $invoiceDate = date('d/m/Y');
    $invoiceNumber = 'INV-' . str_pad($request['id'], 6, '0', STR_PAD_LEFT);
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - ' . $invoiceNumber . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #0d6efd; }
        .company-name { font-size: 20px; margin-top: 10px; }
        .invoice-details { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-info { text-align: right; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #0d6efd; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #dee2e6; }
        .total-row { font-weight: bold; font-size: 18px; background-color: #f8f9fa; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #6c757d; border-top: 2px solid #dee2e6; padding-top: 20px; }
        .stamp { border: 3px solid #198754; color: #198754; padding: 10px 30px; font-size: 24px; font-weight: bold; display: inline-block; transform: rotate(-15deg); margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">' . SITE_NAME . '</div>
        <div class="company-name">Digital Services Platform</div>
        <div style="margin-top: 10px;">Email: support@sansdigitalwork.com | Phone: +91-XXX-XXX-XXXX</div>
    </div>
    
    <div class="invoice-details">
        <div>
            <strong>Bill To:</strong><br>
            ' . htmlspecialchars($request['user_name']) . '<br>
            ' . htmlspecialchars($request['user_email']) . '<br>
            ' . htmlspecialchars($request['user_phone']) . '
        </div>
        <div class="invoice-info">
            <strong>Invoice Number:</strong> ' . $invoiceNumber . '<br>
            <strong>Invoice Date:</strong> ' . $invoiceDate . '<br>
            <strong>Application ID:</strong> #' . $request['id'] . '
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Service Details</div>
        <table>
            <tr>
                <th>Service Name</th>
                <td>' . htmlspecialchars($request['service_name']) . '</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>' . htmlspecialchars($request['service_description']) . '</td>
            </tr>
            <tr>
                <th>Application Date</th>
                <td>' . date('d M Y, h:i A', strtotime($request['created_at'])) . '</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>' . ucfirst($request['application_status']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">Payment Details</div>
        <table>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Service Fees</td>
                <td>₹' . number_format($request['fees'], 2) . '</td>
            </tr>
            <tr>
                <td>Registration Fees</td>
                <td>₹' . number_format($request['registration_fees'], 2) . '</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL AMOUNT</td>
                <td>₹' . number_format($request['fees'] + $request['registration_fees'], 2) . '</td>
            </tr>
            <tr>
                <td>Payment Status</td>
                <td>' . ($request['payment_status'] === 'success' ? '<span style="color: green; font-weight: bold;">PAID ✓</span>' : '<span style="color: red;">PENDING</span>') . '</td>
            </tr>';
    
    if ($request['payment_id']) {
        $html .= '<tr>
                <td>Payment ID</td>
                <td>' . htmlspecialchars($request['payment_id']) . '</td>
            </tr>';
    }
    
    $html .= '</table>
    </div>';
    
    if ($request['payment_status'] === 'success') {
        $html .= '<div style="text-align: center;">
            <div class="stamp">PAID</div>
        </div>';
    }
    
    $html .= '<div class="footer">
        <p><strong>Terms & Conditions:</strong></p>
        <p>1. This is a computer-generated invoice and does not require a signature.</p>
        <p>2. For any queries, please contact support within 7 days.</p>
        <p>3. Please keep this invoice for your records.</p>
        <p style="margin-top: 20px;">&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 * Simple PDF save function (placeholder - use proper library in production)
 */
function saveSimplePDF($path, $html) {
    // For now, save as HTML file that can be printed as PDF
    // In production, use TCPDF, Dompdf, or similar library
    file_put_contents(str_replace('.pdf', '.html', $path), $html);
    
    // Create a simple text file as placeholder
    file_put_contents($path, "Invoice generated. Please view HTML version for full invoice.");
}

/**
 * Download invoice
 */
function downloadInvoice($invoicePath) {
    $fullPath = __DIR__ . '/../invoices/' . $invoicePath;
    
    if (!file_exists($fullPath)) {
        return false;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit();
}

?>
