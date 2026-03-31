<?php
// Get installation options
$installDocuments = isset($_POST['install_documents']);
$installFields = isset($_POST['install_fields']);
$installViews = isset($_POST['install_views']);

echo '<h4><i class="bi bi-download"></i> Installing Components...</h4>';
echo '<p class="text-muted">Please wait while we configure your system</p>';

echo '<div id="installationProgress">';

try {
    $sqlFile = __DIR__ . '/database/professional-setup.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found!");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split into statements
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^SELECT/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        try {
            if (trim($statement)) {
                $db->exec($statement);
                $executed++;
                
                // Show progress for CREATE and INSERT statements
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Table created successfully</div>';
                } elseif (stripos($statement, 'INSERT INTO document_requirements') !== false) {
                    preg_match("/VALUES\s*\((\d+)/i", $statement, $matches);
                    if (isset($matches[1])) {
                        $serviceNames = [
                            1 => 'PAN Card',
                            2 => 'School Admission',
                            3 => 'Voter ID',
                            4 => 'Passport Seva',
                            5 => 'Driving License',
                            6 => 'Bank & Financial',
                            7 => 'Job Placement',
                            8 => 'School & College'
                        ];
                        $serviceName = $serviceNames[$matches[1]] ?? "Service {$matches[1]}";
                        echo '<div class="alert alert-success">';
                        echo '<i class="bi bi-check-circle-fill"></i> Documents added for: <strong>' . $serviceName . '</strong>';
                        echo '</div>';
                    }
                } elseif (stripos($statement, 'INSERT INTO service_fields') !== false) {
                    preg_match("/VALUES\s*\((\d+)/i", $statement, $matches);
                    if (isset($matches[1])) {
                        $serviceNames = [
                            1 => 'PAN Card',
                            2 => 'School Admission',
                            3 => 'Voter ID'
                        ];
                        $serviceName = $serviceNames[$matches[1]] ?? "Service {$matches[1]}";
                        echo '<div class="alert alert-success">';
                        echo '<i class="bi bi-check-circle-fill"></i> Form fields added for: <strong>' . $serviceName . '</strong>';
                        echo '</div>';
                    }
                } elseif (stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Analytics view created</div>';
                }
            }
        } catch (Exception $e) {
            // Ignore duplicate errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    echo '</div>';
    
    if (empty($errors)) {
        echo '<div class="alert alert-success mt-4">';
        echo '<h5><i class="bi bi-check-circle-fill text-success"></i> Installation Complete!</h5>';
        echo '<p><strong>' . $executed . '</strong> SQL statements executed successfully.</p>';
        echo '</div>';
        
        echo '<div class="row mt-4">';
        echo '<div class="col-md-6">';
        echo '<div class="card border-success">';
        echo '<div class="card-body bg-success text-white">';
        echo '<h5><i class="bi bi-file-earmark-text"></i> Documents Configured</h5>';
        echo '<p class="mb-0">30+ document requirements across 8 services</p>';
        echo '</div></div></div>';
        
        echo '<div class="col-md-6">';
        echo '<div class="card border-primary">';
        echo '<div class="card-body bg-primary text-white">';
        echo '<h5><i class="bi bi-ui-checks"></i> Form Fields Created</h5>';
        echo '<p class="mb-0">Dynamic fields for PAN, School, Voter ID</p>';
        echo '</div></div></div>';
        echo '</div>';
        
        echo '<div class="text-center mt-5">';
        echo '<a href="?step=4" class="btn btn-wizard btn-lg">';
        echo '<i class="bi bi-arrow-right-circle"></i> Continue to Summary';
        echo '</a>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning mt-4">';
        echo '<h5><i class="bi bi-exclamation-triangle"></i> Installation Completed with Notes</h5>';
        echo '<p>Some non-critical warnings occurred (likely tables already exist):</p>';
        echo '<ul class="small">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="text-center mt-4">';
        echo '<a href="?step=4" class="btn btn-wizard">';
        echo '<i class="bi bi-arrow-right-circle"></i> Continue Anyway';
        echo '</a>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<h5><i class="bi bi-x-circle-fill"></i> Installation Failed</h5>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    echo '<div class="text-center mt-4">';
    echo '<a href="?step=2" class="btn btn-secondary">';
    echo '<i class="bi bi-arrow-left"></i> Go Back';
    echo '</a>';
    echo '</div>';
}
?>

<style>
#installationProgress .alert {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
