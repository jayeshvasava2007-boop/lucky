<div class="text-center">
    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
    <h3 class="mt-4 text-success">Installation Successful!</h3>
    <p class="lead">Your Document Management System is now ready to use</p>
</div>

<?php
try {
    // Get statistics
    $stats = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM document_requirements) as total_docs,
            (SELECT COUNT(*) FROM service_fields) as total_fields,
            (SELECT COUNT(*) FROM servicesand WHERE status='active') as active_services
    ")->fetch();
?>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-text text-primary" style="font-size: 3rem;"></i>
                <h2 class="mt-3"><?php echo $stats['total_docs']; ?>+</h2>
                <p class="text-muted">Document Requirements</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-ui-checks text-success" style="font-size: 3rem;"></i>
                <h2 class="mt-3"><?php echo $stats['total_fields']; ?>+</h2>
                <p class="text-muted">Dynamic Form Fields</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-collection text-info" style="font-size: 3rem;"></i>
                <h2 class="mt-3"><?php echo $stats['active_services']; ?></h2>
                <p class="text-muted">Active Services</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> What's Next?</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="bi bi-person-check text-primary"></i> Test User Experience</h6>
                <ol>
                    <li>Visit apply-service.php</li>
                    <li>Select any service</li>
                    <li>See dynamic document fields</li>
                    <li>Fill form and upload documents</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-person-gear text-success"></i> Admin Management</h6>
                <ol>
                    <li>Login to admin panel</li>
                    <li>Go to Manage Documents</li>
                    <li>Add/Edit/Delete documents</li>
                    <li>View analytics and reports</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <strong><i class="bi bi-key"></i> Quick Access Links:</strong>
    <div class="row mt-2">
        <div class="col-md-4">
            <a href="apply-service.php" class="btn btn-outline-primary w-100">
                <i class="bi bi-play-circle"></i> Test Apply Service
            </a>
        </div>
        <div class="col-md-4">
            <a href="admin/manage-documents.php" class="btn btn-outline-success w-100">
                <i class="bi bi-person-gear"></i> Admin Panel
            </a>
        </div>
        <div class="col-md-4">
            <a href="index.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<?php } catch (Exception $e) { ?>
    <div class="alert alert-warning">
        Could not fetch statistics. But installation is complete!
    </div>
<?php } ?>

<div class="text-center mt-5">
    <button onclick="window.location.href='index.php'" class="btn btn-wizard btn-lg">
        <i class="bi bi-check-circle"></i> Finish & Go to Dashboard
    </button>
</div>
