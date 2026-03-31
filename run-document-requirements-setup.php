<?php
/**
 * Run Database Migration for User's Document Requirements
 * This script adds document requirements based on user's phpMyAdmin export
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Database Migration: Document Requirements Setup</h2>";
echo "<hr>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Step 1: Checking current document_requirements table...</h3>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM document_requirements");
    $count = $stmt->fetch()['count'];
    echo "<p>Current total records: <strong>$count</strong></p>";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/complete-document-requirements.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL migration file not found! Please ensure user-document-requirements.sql exists.");
    }
    
    echo "<h3>Step 2: Running migration...</h3>";
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        // Skip USE statement as we're already connected to the database
        if (stripos(trim($statement), 'USE') === 0) continue;
        
        // Skip SELECT statements
        if (stripos(trim($statement), 'SELECT') === 0) continue;
        
        echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
        $db->exec($statement);
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: green;'>✓ Migration completed successfully!</strong><br>";
    echo "Document requirements have been set up for ALL services:<br>";
    echo "<ul>";
    echo "<li><strong>PAN Card Services (ID: 1)</strong> - Aadhaar, Photo, Signature</li>";
    echo "<li><strong>Aadhaar Services (ID: 2)</strong> - Aadhaar Card, Address Proof</li>";
    echo "<li><strong>Voter ID (ID: 3)</strong> - Aadhaar, Address Proof, Photo, Age Proof</li>";
    echo "<li><strong>Driving License (ID: 4)</strong> - Aadhaar, Photo, Signature, Learner License, Address & Age Proof</li>";
    echo "<li><strong>Bank Services (ID: 5)</strong> - PAN, Aadhaar, Bank Statement, Photo</li>";
    echo "<li><strong>Job Placement (ID: 6)</strong> - CV/Resume, Photo, Aadhaar, Education Certificates, Mobile</li>";
    echo "<li><strong>School/College Admission (ID: 7)</strong> - 10th & 12th Marksheets, TC, Photo, Aadhaar</li>";
    echo "</ul>";
    echo "</div>";
    
    // Verify the changes
    echo "<h3>Step 3: Verifying changes...</h3>";
    $stmt = $db->query("
        SELECT s.service_name, COUNT(dr.id) as doc_count 
        FROM document_requirements dr 
        JOIN servicesand s ON dr.service_id = s.id 
        GROUP BY s.id, s.service_name
        ORDER BY s.id
    ");
    $results = $stmt->fetchAll();
    
    foreach ($results as $result) {
        echo "<p>✓ <strong>{$result['service_name']}</strong>: {$result['doc_count']} documents configured</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Visit <a href='dashboard.php' target='_blank'>Dashboard</a> to see all available services</li>";
    echo "<li>Click 'Apply' on ANY service to test dynamic document uploads</li>";
    echo "<li>Use the service selector dropdown to switch between services</li>";
    echo "<li>Verify that document fields change based on selected service</li>";
    echo "<li>Test with Driving License, Job Placement, or Admission services</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: red;'>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p style='margin-top: 20px;'><a href='index.php'>← Back to Home</a></p>";
?>
