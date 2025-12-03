<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/lang.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center rounded-top">
                    <h1 class="mb-0 py-2">
                        <i class="bi bi-cloud-arrow-up me-2"></i>
                        <?= lang('import_data') ?? 'Import Data' ?>
                    </h1>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message']['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['message']['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <div id="response-message-container"></div>
                    
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?= lang('info_allowed_file_types') ?? 'Only files with .xlsx and .csv extensions are allowed for import.' ?>
                    </div>

                    <form id="import-form" action="/import_file" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                        <input type="hidden" id="table_name" name="table_name" value="items">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="excelFileInput" class="form-label">
                                    <i class="bi bi-folder-plus me-2"></i><?= lang('select_file') ?? 'Select File' ?>
                                </label>
                                <input type="file" 
                                       name="excel_file" 
                                       id="excelFileInput" 
                                       class="form-control"
                                       accept=".xlsx, .csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, text/csv" 
                                       required>
                                <div id="fileExtensionError" class="text-danger mt-1" style="display: none;"></div>
                            </div>

                            <div class="col-12 d-flex justify-content-end mt-4">
                                <button type="submit" id="import-submit" class="btn btn-primary me-2">
                                    <i class="bi bi-file-earmark-arrow-up me-2"></i><?= lang('import_from_excel') ?? 'Import' ?>
                                </button>
                                <a href="/list" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?? 'Cancel' ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const importForm = document.getElementById('import-form');
        const fileInput = document.getElementById('excelFileInput');
        const responseMessageContainer = document.getElementById('response-message-container');
        const fileExtensionError = document.getElementById('fileExtensionError');
        const importSubmitBtn = document.getElementById('import-submit');
        const importCancelBtn = document.querySelector('a.btn-secondary');

        // Add event listener to file input for validation
        fileInput.addEventListener('change', validateFileExtension);

        importForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const isValid = validateFileExtension();
            if (!isValid) {
                return;
            }

            // Show a temporary message while importing
            showMessage('<?= lang('import_in_progress') ?? 'Import is in progress. Please wait...' ?>', 'info');

            importSubmitBtn.disabled = true;
            if (importCancelBtn) {
                 importCancelBtn.disabled = true;
            }
           
            importSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= lang('importing') ?? 'Importing' ?>...';

            const formData = new FormData(importForm);

            try {
                const response = await fetch(importForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Server returned an error status: ' + response.status);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server did not return a valid JSON response.');
                }
                
                const data = await response.json();

                // Here is the fix: use the 'success' property from the JSON response
                // and map it to a Bootstrap alert type.
                const alertType = data.success ? 'success' : 'danger';
                showMessage(data.message, alertType);

            } catch (error) {
                console.error('Error:', error);
                showMessage('<?= lang('error_unexpected_import') ?? 'An unexpected error occurred during import.' ?>', 'danger');
            } finally {
                importSubmitBtn.disabled = false;
                if (importCancelBtn) {
                    importCancelBtn.disabled = false;
                }
                importSubmitBtn.innerHTML = `<i class="bi bi-file-earmark-arrow-up me-2"></i><?= lang('import_from_excel') ?? 'Import' ?>`;
            }
        });

        function validateFileExtension() {
            const filePath = fileInput.value;
            fileExtensionError.style.display = 'none';
            fileExtensionError.textContent = '';
            
            if (filePath) {
                const allowedExtensions = /(\.xlsx|\.csv)$/i;
                if (!allowedExtensions.exec(filePath)) {
                    fileExtensionError.textContent = '<?= lang('error_invalid_file_extension') ?? 'Invalid file type. Only .xlsx and .csv files are allowed.' ?>';
                    fileExtensionError.style.display = 'block';
                    return false;
                }
            }
            return true;
        }

        // Helper function to display messages
        function showMessage(message, type) {
            const iconClass = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';
            
            responseMessageContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${iconClass} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }
    });
</script>