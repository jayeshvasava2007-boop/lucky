<h4><i class="bi bi-gear"></i> Configuration Options</h4>
<p class="text-muted">Choose what you want to install</p>

<form method="POST" action="?step=3" id="configForm">
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="install_documents" id="installDocuments" checked>
                <label class="form-check-label fw-bold" for="installDocuments">
                    <i class="bi bi-file-earmark-text text-primary"></i> Document Requirements
                </label>
                <small class="text-muted d-block mt-1">
                    Install document requirements for all services (Required for dynamic uploads)
                </small>
            </div>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="install_fields" id="installFields" checked>
                <label class="form-check-label fw-bold" for="installFields">
                    <i class="bi bi-ui-checks text-primary"></i> Dynamic Form Fields
                </label>
                <small class="text-muted d-block mt-1">
                    Install custom form fields for services (PAN, School, Voter ID)
                </small>
            </div>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="install_views" id="installViews" checked>
                <label class="form-check-label fw-bold" for="installViews">
                    <i class="bi bi-graph-up text-primary"></i> Analytics Views
                </label>
                <small class="text-muted d-block mt-1">
                    Create database views for reporting and analytics
                </small>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong><i class="bi bi-lightbulb"></i> Recommendation:</strong> Keep all options selected for complete functionality.
    </div>
    
    <div class="d-flex justify-content-between mt-4">
        <a href="?step=1" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button type="submit" class="btn btn-wizard">
            <i class="bi bi-play-circle"></i> Start Installation
        </button>
    </div>
</form>

<script>
document.getElementById('configForm').addEventListener('submit', function(e) {
    const anyChecked = document.querySelector('input[name="install_documents"]:checked') ||
                      document.querySelector('input[name="install_fields"]:checked') ||
                      document.querySelector('input[name="install_views"]:checked');
    
    if (!anyChecked) {
        e.preventDefault();
        alert('Please select at least one option to install.');
    }
});
</script>
