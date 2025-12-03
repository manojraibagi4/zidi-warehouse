<?php
// src/views/export_list.php

require_once __DIR__ . '/../utils/Helper.php';
use Utils\Helper;

// Initialize filter params for export
$filter_params = [];
if (!empty($_GET['productname'])) $filter_params['productname'] = $_GET['productname'];
if (!empty($_GET['manufacturer'])) $filter_params['manufacturer'] = $_GET['manufacturer'];
if (!empty($_GET['size'])) $filter_params['size'] = $_GET['size'];
if (!empty($_GET['color'])) $filter_params['color'] = $_GET['color'];
if (isset($_GET['grafted']) && $_GET['grafted'] !== '') $filter_params['grafted'] = $_GET['grafted'];
if (!empty($_GET['club'])) $filter_params['club'] = $_GET['club'];

$export_query_string = http_build_query($filter_params);
$export_base_url = "export_"; 

// Helper function to safely escape HTML with null handling
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<div class="container mt-4">
    <input type="hidden" id="csrf_token_export" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
    <?php if (empty($items) || !is_array($items)): ?>
        <p class="alert alert-info" id="no-items-message">
            <?= h(lang('no_items_found')) ?> <a href="/create" id="add-new-item-link"><?= h(lang('add_new_item')) ?></a>.
        </p>
    <?php else: ?>
        <div class="card shadow-lg border-0 rounded-3">
            <div class="card-header bg-primary text-white text-center rounded-top">
                <h1 class="mb-3" id="export-page-title">
                    <i class="bi bi-cloud-arrow-down me-2"></i>
                    <?= h(lang('export') ?? 'Export') ?>
                </h1>
            </div>
        </div>

        <div class="mt-3 mb-4" id="export-toolbar">
            <button class="btn btn-success me-2 export-btn" data-format="excel" id="export-excel-btn">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i><?= h(lang('export_to_excel')) ?>
            </button>
            <button class="btn btn-success me-2 export-btn" data-format="csv" id="export-csv-btn">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i><?= h(lang('export_to_csv')) ?>
            </button>
            <button class="btn btn-danger export-btn" data-format="pdf" id="export-pdf-btn">
                <i class="bi bi-file-earmark-pdf me-2"></i><?= h(lang('export_to_pdf')) ?>
            </button>
        </div>

        <!-- ✅ UPDATED FILTER FORM - Now includes article_no, supplier, and category -->
        <form method="GET" action="/export" class="mb-4 p-3 bg-light rounded shadow-sm" id="export-filter-form">
            <div class="row g-3">
                <!-- Product Name Filter -->
                <div class="col-12 col-md-4">
                    <label for="productname-filter" class="form-label">
                        <i class="bi bi-tag me-2"></i><?= h(lang('product_name')) ?>
                    </label>
                    <input type="text" class="form-control" id="productname-filter" name="productname"
                        value="<?= h($filters['productname'] ?? '') ?>">
                </div>
                
                <!-- ✅ NEW: Article Number Filter -->
                <div class="col-12 col-md-4">
                    <label for="article_no-filter" class="form-label">
                        <i class="bi bi-upc-scan me-2"></i><?= h(lang('article_no')) ?>
                    </label>
                    <input type="text" class="form-control" id="article_no-filter" name="article_no"
                        value="<?= h($filters['article_no'] ?? '') ?>"
                        placeholder="<?= h(lang('search_by_article_no')) ?>">
                </div>
                
                <!-- Manufacturer Filter -->
                <div class="col-12 col-md-4">
                    <label for="manufacturer-filter" class="form-label">
                        <i class="bi bi-building me-2"></i><?= h(lang('manufacturer')) ?>
                    </label>
                    <select class="form-select" id="manufacturer-filter" name="manufacturer">
                        <option value=""><?= h(lang('all_manufacturers')) ?></option>
                        <?php if (is_array($allManufacturers)): ?>
                            <?php foreach ($allManufacturers as $m): ?>
                                <option value="<?= h($m['name'] ?? '') ?>"
                                    <?= (isset($filters['manufacturer']) && $filters['manufacturer'] == ($m['name'] ?? '')) ? 'selected' : '' ?>>
                                    <?= h($m['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Color Filter -->
                <div class="col-12 col-md-4">
                    <label for="color-filter" class="form-label">
                        <i class="bi bi-palette me-2"></i><?= h(lang('color')) ?>
                    </label>
                    <input type="text" class="form-control" id="color-filter" name="color"
                        value="<?= h($filters['color'] ?? '') ?>">
                </div>
                
                <!-- Size Filter -->
                <div class="col-12 col-md-4">
                    <label for="size-filter" class="form-label">
                        <i class="bi bi-rulers me-2"></i><?= h(lang('size')) ?>
                    </label>
                    <select class="form-select" id="size-filter" name="size">
                        <option value=""><?= h(lang('all_sizes')) ?></option>
                        <?php if (is_array($allSizes)): ?>
                            <?php foreach ($allSizes as $s): ?>
                                <option value="<?= h($s['name'] ?? '') ?>"
                                    <?= (isset($filters['size']) && $filters['size'] == ($s['name'] ?? '')) ? 'selected' : '' ?>>
                                    <?= h($s['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- ✅ NEW: Supplier Filter -->
                <div class="col-12 col-md-4">
                    <label for="supplier-filter" class="form-label">
                        <i class="bi bi-truck me-2"></i><?= h(lang('supplier')) ?>
                    </label>
                    <select class="form-select" id="supplier-filter" name="supplier">
                        <option value=""><?= h(lang('all_suppliers')) ?></option>
                        <?php if (isset($allSuppliers) && is_array($allSuppliers)): ?>
                            <?php foreach ($allSuppliers as $supplier): ?>
                                <option value="<?= h($supplier['name'] ?? '') ?>"
                                    <?= (isset($filters['supplier']) && $filters['supplier'] == ($supplier['name'] ?? '')) ? 'selected' : '' ?>>
                                    <?= h($supplier['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- ✅ NEW: Category Filter -->
                <div class="col-12 col-md-4">
                    <label for="category-filter" class="form-label">
                        <i class="bi bi-grid me-2"></i><?= h(lang('category')) ?>
                    </label>
                    <select class="form-select" id="category-filter" name="category">
                        <option value=""><?= h(lang('all_categories')) ?></option>
                        <?php if (isset($allCategories) && is_array($allCategories)): ?>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= h($cat['name'] ?? '') ?>"
                                    <?= (isset($filters['category']) && $filters['category'] == ($cat['name'] ?? '')) ? 'selected' : '' ?>>
                                    <?= h($cat['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Grafted Filter -->
                <div class="col-12 col-md-4">
                    <label for="grafted-filter" class="form-label">
                        <i class="bi bi-patch-check me-2"></i><?= h(lang('grafted')) ?>
                    </label>
                    <select class="form-select" id="grafted-filter" name="grafted">
                        <option value=""><?= h(lang('all')) ?></option>
                        <option value="1" <?= (isset($filters['grafted']) && $filters['grafted'] === '1') ? 'selected' : '' ?>><?= h(lang('yes')) ?></option>
                        <option value="0" <?= (isset($filters['grafted']) && $filters['grafted'] === '0') ? 'selected' : '' ?>><?= h(lang('no')) ?></option>
                    </select>
                </div>
                
                <!-- Club Filter -->
                <div class="col-12 col-md-4">
                    <label for="club-filter" class="form-label">
                        <i class="bi bi-journal me-2"></i><?= h(lang('club')) ?>
                    </label>
                    <select class="form-select" id="club-filter" name="club">
                        <option value=""><?= h(lang('all_clubs')) ?></option>
                        <?php if (is_array($allClubs)): ?>
                            <?php foreach ($allClubs as $c): ?>
                                <option value="<?= h($c['name'] ?? '') ?>"
                                    <?= (isset($filters['club']) && $filters['club'] == ($c['name'] ?? '')) ? 'selected' : '' ?>>
                                    <?= h($c['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Filter Buttons -->
                <div class="col-12" id="filter-buttons">
                    <button type="submit" class="btn btn-primary me-2" id="apply-filters-btn">
                        <i class="bi bi-funnel me-2"></i><?= h(lang('apply_filters')) ?>
                    </button>
                    <a href="/export" class="btn btn-secondary" id="clear-filters-btn">
                        <i class="bi bi-x-circle me-2"></i><?= h(lang('clear_filters')) ?>
                    </a>
                </div>
            </div>
        </form>

        <!-- SAVED FILTERS SECTION -->
        <?php if (!empty($savedFilters)): ?>
            <div class="mb-4 p-3 bg-light rounded shadow-sm">
                <h5 class="mb-3">
                    <i class="bi bi-bookmark-star me-2"></i><?= h(lang('saved_filters')) ?>
                </h5>

                <div class="d-flex flex-wrap gap-2" id="savedFiltersContainer">
                    <?php foreach ($savedFilters as $savedFilter): ?>
                        <div class="saved-filter-item" id="savedFilter_<?= h($savedFilter['id'] ?? '') ?>">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm me-1 apply-saved-filter-export"
                                    data-filter-id="<?= h($savedFilter['id'] ?? '') ?>">
                                <?= h($savedFilter['name'] ?? '') ?>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm delete-saved-filter-export"
                                    data-filter-id="<?= h($savedFilter['id'] ?? '') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- SAVE CURRENT FILTER SECTION -->
        <div class="mb-4 p-3 bg-light rounded shadow-sm">
            <h5 class="mb-3"><i class="bi bi-save me-2"></i><?= h(lang('save_current_filter')) ?></h5>
            <form id="saveFilterFormExport" method="POST">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                <div class="input-group">
                    <input type="text" id="saveFilterInputExport" name="name" class="form-control" 
                        placeholder="<?= h(lang('enter_filter_name')) ?>" required>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i><?= h(lang('save')) ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive" id="export-table-container">
            <table class="table table-hover table-striped shadow-sm rounded overflow-hidden" id="export-table">
                <thead class="table-dark" id="table-header">
                    <tr>
                        <th scope="col"><?= Helper::renderSortHeader('id', '#', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('image', lang('image') ?? 'Image', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('productname', lang('product_name') ?? 'Product Name', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('manufacturer', lang('manufacturer') ?? 'Manufacturer', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('size', lang('size') ?? 'Size', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('color', lang('color') ?? 'Color', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('quantity', lang('quantity') ?? 'Quantity', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('grafted', lang('grafted') ?? 'Grafted', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('club', lang('club') ?? 'Club', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('expiration_year', lang('expiration_year') ?? 'Expiration Year', $filters ?? []) ?></th>
                        <th scope="col"><?= Helper::renderSortHeader('last_change', lang('last_change') ?? 'Last Change', $filters ?? []) ?></th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <?php foreach ($items as $item): ?>
                        <tr id="item-row-<?= h($item['id'] ?? '') ?>"
                            <?php if (!empty($item['description'])): ?>
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                data-bs-html="true"
                                title="<strong>Description:</strong><br><?= h($item['description'] ?? '') ?>"
                            <?php endif; ?>
                            class="export-table-row"
                            style="cursor: default;">
                            <th scope="row"><?= h($item['id'] ?? '') ?></th>
                            <td>
                                <?php if (!empty($item['img'])): ?>
                                    <img src="data:<?= h($item['mime_type'] ?? 'image/png') ?>;base64,<?= base64_encode($item['img']) ?>"
                                             alt="<?= h($item['productname'] ?? 'Product Image') ?>"
                                             class="img-thumbnail"
                                             style="width:50px; height:50px; object-fit:cover;">
                                <?php else: ?>
                                    <img src="/img/default-placeholder.png"
                                             alt="<?= h($item['productname'] ?? 'No Image') ?>"
                                             class="img-thumbnail"
                                             style="width:50px; height:50px; object-fit:cover;">
                                <?php endif; ?>
                            </td>
                            <td><?= h($item['productname'] ?? '') ?></td>
                            <td><?= h($item['manufacturer'] ?? '') ?></td>
                            <td><?= h($item['size'] ?? '') ?></td>
                            <td><?= h($item['color'] ?? '') ?></td>
                            <td><?= h($item['quantity'] ?? '') ?></td>
                            <td><?= ($item['grafted'] ?? false) ? h(lang('yes') ?? 'Yes') : h(lang('no') ?? 'No') ?></td>
                            <td><?= h($item['club'] ?? '-') ?></td>
                            <td><?= h($item['expiration_year'] ?? '') ?></td>
                            <td>
                                <?php
                                if (!empty($item['last_change'])) {
                                    try {
                                        $dt = new DateTime($item['last_change'], new DateTimeZone('UTC'));
                                        $dt->setTimezone(new DateTimeZone($settings->getTimeZone() ?? 'UTC'));
                                        echo h($dt->format(($settings->getDateFormat() ?? 'Y-m-d') . ' H:i:s'));
                                    } catch (Exception $e) {
                                        echo h($item['last_change']);
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="d-md-none text-center text-muted mt-2 small" id="swipe-info">
                <?= h(lang('swipe_to_view_more') ?? 'Swipe to view more') ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav id="paginationNav">
            <ul class="pagination justify-content-center" id="paginationList">
                <?php if ($page > 1): ?>
                    <li class="page-item" id="prevPageItem">
                        <a id="prevPageLink" class="page-link d-flex align-items-center pagination-link-ajax" 
                            href="?<?= h(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">
                            <i class="bi bi-arrow-left-square-fill me-1"></i> <?= h(lang('previous') ?? 'Previous') ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled" id="prevPageItemDisabled">
                        <span id="prevPageSpan" class="page-link d-flex align-items-center">
                            <i class="bi bi-arrow-left-square-fill me-1"></i> <?= h(lang('previous') ?? 'Previous') ?>
                        </span>
                    </li>
                <?php endif; ?>

                <?php
                $window = 5; 
                $half = floor($window / 2);
                $start = max(2, $page - $half);
                $end = min($totalPages - 1, $page + $half);

                if ($page <= $half + 2) {
                    $start = 2;
                    $end = min($totalPages - 1, $window + 1);
                } elseif ($page >= $totalPages - $half - 1) {
                    $end = $totalPages - 1;
                    $start = max(2, $totalPages - $window);
                }

                echo '<li class="page-item '.($page==1?'active':'').'" id="pageItem-1">
                            <a class="page-link pagination-link-ajax" id="pageLink-1" href="?' . h(http_build_query(array_merge($_GET, ['page' => 1]))) . '">1</a>
                        </li>';

                if ($start > 2) {
                    echo '<li class="page-item disabled" id="pageEllipsisStart"><span class="page-link">…</span></li>';
                }

                for ($i = $start; $i <= $end; $i++) {
                    echo '<li class="page-item '.($i==$page?'active':'').'" id="pageItem-'.$i.'">
                                <a class="page-link pagination-link-ajax" id="pageLink-'.$i.'" href="?' . h(http_build_query(array_merge($_GET, ['page' => $i]))) . '">' . $i . '</a>
                            </li>';
                }

                if ($end < $totalPages - 1) {
                    echo '<li class="page-item disabled" id="pageEllipsisEnd"><span class="page-link">…</span></li>';
                }

                if ($totalPages > 1) {
                    echo '<li class="page-item '.($page==$totalPages?'active':'').'" id="pageItem-'.$totalPages.'">
                                <a class="page-link pagination-link-ajax" id="pageLink-'.$totalPages.'" href="?' . h(http_build_query(array_merge($_GET, ['page' => $totalPages]))) . '">' . $totalPages . '</a>
                            </li>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item" id="nextPageItem">
                        <a id="nextPageLink" class="page-link d-flex align-items-center pagination-link-ajax" 
                            href="?<?= h(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">
                            <?= h(lang('next') ?? 'Next') ?> <i class="bi bi-arrow-right-square-fill ms-1"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled" id="nextPageItemDisabled">
                        <span id="nextPageSpan" class="page-link d-flex align-items-center">
                            <?= h(lang('next') ?? 'Next') ?> <i class="bi bi-arrow-right-square-fill ms-1"></i>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportButtons = document.querySelectorAll('.export-btn');
    const saveFilterFormExport = document.getElementById('saveFilterFormExport');
    const exportMessageContainer = document.createElement('div');
    exportMessageContainer.id = 'export-message-container';
    exportMessageContainer.className = 'mt-3';
    document.querySelector('.container.mt-4').insertBefore(exportMessageContainer, document.getElementById('export-toolbar'));

    /**
     * Initialize Bootstrap tooltips for description hover
     */
    function initExportTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('#export-table [data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 500, hide: 100 },
                trigger: 'hover',
                html: true
            });
        });
    }

    /**
     * Get CSRF token from various possible locations
     */
    function getCsrfToken() {
        // Try hidden input first
        const hiddenInput = document.getElementById('csrf_token_export');
        if (hiddenInput && hiddenInput.value) {
            return hiddenInput.value;
        }
        
        // Try meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag && metaTag.content) {
            return metaTag.content;
        }
        
        // Try any csrf_token input
        const anyInput = document.querySelector('input[name="csrf_token"]');
        if (anyInput && anyInput.value) {
            return anyInput.value;
        }
        
        console.error('CSRF token not found');
        return null;
    }

    /**
     * Shows a message to the user in the export page.
     * @param {string} message The message to display.
     * @param {string} type The type of message ('success' or 'danger').
     */
    function showExportMessage(message, type) {
        if (!exportMessageContainer) return;
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        exportMessageContainer.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    /**
     * Retrieves the current values from the export filter form.
     * @returns {object} An object containing the filter key-value pairs.
     */
    function getCurrentExportFilters() {
        const form = document.getElementById('export-filter-form');
        if (!form) return {};
        const formData = new FormData(form);
        const filters = {};
        for (const [key, value] of formData.entries()) {
            if (value) {
                filters[key] = value;
            }
        }
        return filters;
    }

    // Initialize tooltips on page load
    initExportTooltips();

    // Export buttons functionality
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            let exportBaseUrl = '';

            // Map format to your routes
            if (format === 'excel') exportBaseUrl = '/export_excel';
            else if (format === 'csv') exportBaseUrl = '/export_csv';
            else if (format === 'pdf') exportBaseUrl = '/export_pdf';

            // Collect all filter values from the form dynamically
            const form = document.querySelector('#export-filter-form');
            const formData = new FormData(form);
            formData.append('format', format);

            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value !== '') {
                    params.append(key, value);
                }
            }

            const url = `${exportBaseUrl}?${params.toString()}`;
            console.log('Export URL:', url);

            // Loading indicator
            const originalText = this.innerHTML;
            this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`;
            this.disabled = true;

            fetch(url, { method: 'GET' })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');

                    let filename = 'items_export';
                    if (format === 'excel') filename += '.xlsx';
                    else if (format === 'csv') filename += '.csv';
                    else if (format === 'pdf') filename += '.pdf';

                    return response.blob().then(blob => ({ blob, filename }));
                })
                .then(({ blob, filename }) => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showExportMessage('Export completed successfully!', 'success');
                })
                .catch(error => {
                    console.error('Export failed:', error);
                    showExportMessage('Export failed. Please try again.', 'danger');
                })
                .finally(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
        });
    });

    // Handle save filter form submission for export page
    if (saveFilterFormExport) {
        saveFilterFormExport.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const currentFilters = getCurrentExportFilters();
            
            // Add CSRF token and source page
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            formData.append('source_page', 'export');
            
            for (const [key, value] of Object.entries(currentFilters)) {
                if (value) {
                    formData.append(`filters[${key}]`, value);
                }
            }
            
            fetch('/save_filter', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showExportMessage(data.message, 'success');
                    this.reset();
                    // Reload the page to show the new saved filter button
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showExportMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showExportMessage('Failed to save filter', 'danger');
            });
        });
    }

    // Handle apply saved filter button for export page
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.apply-saved-filter-export');
        if (!btn) return;

        e.preventDefault(); 

        // Highlight the active filter
        const allButtons = document.querySelectorAll('.apply-saved-filter-export');
        allButtons.forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-primary');
        });
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
        
        // Apply the filter - IMPORTANT: Add source parameter
        const filterId = btn.getAttribute('data-filter-id');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
        btn.disabled = true;

        // Add source parameter to tell backend this is from export page
        fetch(`/apply_saved_filter/${filterId}?source=export`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Server error occurred.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showExportMessage('Filter applied successfully', 'success');
                // Use the redirect URL provided by backend (it should now point to export)
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 500);
            } else {
                throw new Error(data.message || 'Failed to apply filter');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showExportMessage(error.message, 'danger');
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Reset button style on error
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
    });

    // Handle delete saved filter button for export page
    document.body.addEventListener('click', function(e) {
        const deleteFilterBtn = e.target.closest('.delete-saved-filter-export');
        if (deleteFilterBtn) {
            e.preventDefault();
            const filterId = deleteFilterBtn.dataset.filterId;
            if (confirm('Are you sure you want to delete this saved filter?')) {
                const originalText = deleteFilterBtn.innerHTML;
                deleteFilterBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                deleteFilterBtn.disabled = true;

                // Get CSRF token using our helper function
                const csrfToken = getCsrfToken();

                if (!csrfToken) {
                    showExportMessage('CSRF token missing. Please refresh the page and try again.', 'danger');
                    deleteFilterBtn.innerHTML = originalText;
                    deleteFilterBtn.disabled = false;
                    return;
                }

                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                fetch(`/delete_saved_filter/${filterId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showExportMessage('Filter deleted successfully', 'success');
                        const filterElement = document.getElementById(`savedFilter_${filterId}`);
                        if (filterElement) {
                            filterElement.remove();
                        }
                        
                        // If no saved filters left, show message
                        if (document.querySelectorAll('.saved-filter-item').length === 0) {
                            const savedFiltersContainer = document.getElementById('savedFiltersContainer');
                            if (savedFiltersContainer) {
                                savedFiltersContainer.innerHTML = '<p class="text-muted mb-0">No saved filters found.</p>';
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Failed to delete filter');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showExportMessage(error.message, 'danger');
                    deleteFilterBtn.innerHTML = originalText;
                    deleteFilterBtn.disabled = false;
                });
            }
            return;
        }
    });

    // Handle pagination links to stay on export page
    document.addEventListener('click', function(e) {
        const paginationLink = e.target.closest('.pagination-link-ajax');
        if (paginationLink) {
            e.preventDefault();
            // Ensure the link stays on export page
            let href = paginationLink.href;
            if (href.includes('/list?')) {
                href = href.replace('/list?', '/export?');
            }
            window.location.href = href;
        }
    });

});
</script>