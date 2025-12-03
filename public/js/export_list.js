document.addEventListener('DOMContentLoaded', function() {
    const exportButtons = document.querySelectorAll('.export-btn');
    const exportQueryString = '<?= htmlspecialchars($export_query_string) ?>';
    // Changed exportBaseUrl to a clean URL
    const exportBaseUrl = 'export_';

    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            // The new URL structure will be /export/excel, /export/csv, etc.
            const url = `${exportBaseUrl}${format}?${exportQueryString}`;
            console.log(url);
            // Show a loading indicator
            const originalText = this.innerHTML;
            this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`;
            this.disabled = true;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    const contentType = response.headers.get("content-type");
                    let filename = 'items_export';
                    if (format === 'excel') {
                        filename += '.xlsx';
                    } else if (format === 'csv') {
                        filename += '.csv';
                    } else if (format === 'pdf') {
                        filename += '.pdf';
                    }
                    
                    return response.blob().then(blob => ({ blob, filename, contentType }));
                })
                .then(({ blob, filename, contentType }) => {
                    // Create a link element to trigger the download
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alertDiv.setAttribute('role', 'alert');
                    alertDiv.innerHTML = `Export successful! Your download should start shortly.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                    document.querySelector('#export-toolbar').after(alertDiv);
                    setTimeout(() => alertDiv.remove(), 5000);
                })
                .catch(error => {
                    console.error('Export failed:', error);
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alertDiv.setAttribute('role', 'alert');
                    alertDiv.innerHTML = `Export failed: ${error.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                    document.querySelector('#export-toolbar').after(alertDiv);
                    setTimeout(() => alertDiv.remove(), 5000);
                })
                .finally(() => {
                    // Reset button state
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
        });
    });
});