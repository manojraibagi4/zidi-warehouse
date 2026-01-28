<?php
use Utils\Helper; // Add this line to import the Helper class
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Helper function to safely escape HTML with null handling
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<?php if (!$isAjax): ?>
<div id="item-list-container" class="container-fluid py-4">
<?php endif; ?>
<div class="row justify-content-center">
    <div class="col-lg-12">
        <?php if (empty($items) || !is_array($items)): ?>
            <div id="noItemsContainer" class="card shadow-lg border-0 rounded-3">
                <div class="card-body p-5 text-center">
                    <h4 class="mb-3 text-muted"><?= lang('no_items_found') ?></h4>
                    <p class="text-muted"><?= lang('no_items_found_message') ?></p>
                    <a id="addNewItemLink" href="/create" class="btn btn-primary mt-3 nav-link-ajax">
                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_new_item') ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top">
                    <h2 class="h4 mb-0 py-2" id="itemOverviewTitle">
                        <i class="bi bi-boxes me-2"></i><?= lang('item_overview') ?>
                    </h2>
                </div>
                <div class="card-body p-4">
                    <div id="list-message-container" class="mt-3"></div>
                    
                    <form id="filterForm" method="GET" action="/list" class="mb-4 p-3 bg-light rounded shadow-sm">
                        <div class="row g-3">
                            <!-- Product Name Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterProductname" class="form-label">
                                    <i class="bi bi-tag me-2"></i><?= lang('product_name') ?>
                                </label>
                                <input type="text" class="form-control" id="filterProductname" name="productname" 
                                    value="<?= h($filters['productname'] ?? '') ?>">
                            </div>
                            
                            <!-- ✅ NEW: Article Number Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterArticleNo" class="form-label">
                                    <i class="bi bi-upc-scan me-2"></i><?= lang('article_no') ?>
                                </label>
                                <input type="text" class="form-control" id="filterArticleNo" name="article_no" 
                                    value="<?= h($filters['article_no'] ?? '') ?>" 
                                    placeholder="<?= lang('search_by_article_no') ?>">
                            </div>
                            
                            <!-- Manufacturer Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterManufacturer" class="form-label">
                                    <i class="bi bi-building me-2"></i><?= lang('manufacturer') ?>
                                </label>
                                <select class="form-select" id="filterManufacturer" name="manufacturer">
                                    <option value=""><?= lang('all_manufacturers') ?></option>
                                    <?php if (is_array($allManufacturers)): ?>
                                        <?php foreach ($allManufacturers as $m): ?>
                                            <option value="<?= h($m['name']) ?>" 
                                                <?= (isset($filters['manufacturer']) && $filters['manufacturer'] == $m['name']) ? 'selected' : '' ?>>
                                                <?= h($m['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- Color Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterColor" class="form-label">
                                    <i class="bi bi-palette me-2"></i><?= lang('color') ?>
                                </label>
                                <input type="text" class="form-control" id="filterColor" name="color" 
                                    value="<?= h($filters['color'] ?? '') ?>">
                            </div>
                            
                            <!-- Size Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterSize" class="form-label">
                                    <i class="bi bi-rulers me-2"></i><?= lang('size') ?>
                                </label>
                                <select class="form-select" id="filterSize" name="size">
                                    <option value=""><?= lang('all_sizes') ?></option>
                                    <?php if (is_array($allSizes)): ?>
                                        <?php foreach ($allSizes as $s): ?>
                                            <option value="<?= h($s['name']) ?>" 
                                                <?= (isset($filters['size']) && $filters['size'] == $s['name']) ? 'selected' : '' ?>>
                                                <?= h($s['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- ✅ NEW: Supplier Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterSupplier" class="form-label">
                                    <i class="bi bi-truck me-2"></i><?= lang('supplier') ?>
                                </label>
                                <select class="form-select" id="filterSupplier" name="supplier">
                                    <option value=""><?= lang('all_suppliers') ?></option>
                                    <?php if (isset($allSuppliers) && is_array($allSuppliers)): ?>
                                        <?php foreach ($allSuppliers as $supplier): ?>
                                            <option value="<?= h($supplier['name']) ?>" 
                                                <?= (isset($filters['supplier']) && $filters['supplier'] == $supplier['name']) ? 'selected' : '' ?>>
                                                <?= h($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- ✅ NEW: Category Filter -->
                            <div class="col-12 col-md-4">
                                <label for="filterCategory" class="form-label">
                                    <i class="bi bi-grid me-2"></i><?= lang('category') ?>
                                </label>
                                            <select class="form-select" id="filterCategory" name="category">
                                                <option value=""><?= lang('all_categories') ?></option>
                                                <?php if (isset($allCategories) && is_array($allCategories)): ?>
                                                    <?php foreach ($allCategories as $cat): ?>
                                                        <option value="<?= h($cat['name']) ?>" 
                                                            <?= (isset($filters['category']) && $filters['category'] == $cat['name']) ? 'selected' : '' ?>>
                                                            <?= h($cat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Grafted Filter -->
                                        <div class="col-12 col-md-4">
                                            <label for="filterGrafted" class="form-label">
                                                <i class="bi bi-patch-check me-2"></i><?= lang('grafted') ?>
                                            </label>
                                            <select class="form-select" id="filterGrafted" name="grafted">
                                                <option value=""><?= lang('all') ?></option>
                                                <option value="1" <?= (isset($filters['grafted']) && $filters['grafted'] === '1') ? 'selected' : '' ?>><?= lang('yes') ?></option>
                                                <option value="0" <?= (isset($filters['grafted']) && $filters['grafted'] === '0') ? 'selected' : '' ?>><?= lang('no') ?></option>
                                            </select>
                                        </div>
                                        
                                        <!-- Club Filter -->
                                        <div class="col-12 col-md-4">
                                            <label for="filterClub" class="form-label">
                                                <i class="bi bi-journal me-2"></i><?= lang('club') ?>
                                            </label>
                                            <select class="form-select" id="filterClub" name="club">
                                                <option value=""><?= lang('all_clubs') ?></option>
                                                <?php if (is_array($allClubs)): ?>
                                                    <?php foreach ($allClubs as $c): ?>
                                                        <option value="<?= h($c['name']) ?>" 
                                                            <?= (isset($filters['club']) && $filters['club'] == $c['name']) ? 'selected' : '' ?>>
                                                            <?= h($c['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <!-- Low Stock Filter -->
                                        <div class="col-12 col-md-4">
                                            <label for="filterLowStock" class="form-label">
                                                <i class="bi bi-exclamation-triangle me-2"></i><?= lang('low_stock_filter') ?>
                                            </label>
                                            <select class="form-select" id="filterLowStock" name="lowstock">
                                                <option value=""><?= lang('all') ?></option>
                                                <option value="1" <?= (isset($filters['lowstock']) && $filters['lowstock'] === '1') ? 'selected' : '' ?>><?= lang('yes') ?></option>
                                            </select>
                                        </div>

                                        <!-- Track Expiry Filter -->
                                        <div class="col-12 col-md-4">
                                            <label for="filterTrackExpiry" class="form-label">
                                                <i class="bi bi-calendar-check me-2"></i><?= lang('track_expiry_filter') ?>
                                            </label>
                                            <select class="form-select" id="filterTrackExpiry" name="trackexpiry">
                                                <option value=""><?= lang('all') ?></option>
                                                <option value="1" <?= (isset($filters['trackexpiry']) && $filters['trackexpiry'] === '1') ? 'selected' : '' ?>><?= lang('track_expiry') ?></option>
                                            </select>
                                        </div>

                                        <!-- Filter Buttons -->
                                        <div class="col-12">
                                            <button id="applyFiltersBtn" type="submit" class="btn btn-primary me-2">
                                                <i class="bi bi-funnel me-2"></i><?= lang('apply_filters') ?>
                                            </button>
                                            <a id="clearFiltersBtn" href="/list" class="btn btn-secondary">
                                                <i class="bi bi-x-circle me-2"></i><?= lang('clear_filters') ?>
                                            </a>
                                        </div>
                                    </div>
                                </form>

                    <?php if (!empty($savedFilters)): ?>
                        <div class="mb-4 p-3 bg-light rounded shadow-sm">
                            <h5 class="mb-3">
                                <i class="bi bi-bookmark-star me-2"></i><?= lang('saved_filters') ?>
                            </h5>

                            <div class="d-flex flex-wrap gap-2" id="savedFiltersContainer">
                                <?php foreach ($savedFilters as $savedFilter): ?>
                                    <div class="saved-filter-item" id="savedFilter_<?= $savedFilter['id'] ?>">
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm me-1 apply-saved-filter"
                                                data-filter-id="<?= $savedFilter['id'] ?>">
                                            <?= h($savedFilter['name']) ?>
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm delete-saved-filter"
                                                data-filter-id="<?= $savedFilter['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>


                   <div class="mb-4 p-3 bg-light rounded shadow-sm">
                        <h5 class="mb-3"><i class="bi bi-save me-2"></i><?= lang('save_current_filter') ?></h5>
                        <form id="saveFilterForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                            <div class="input-group">
                                <input type="text" id="saveFilterInput" name="name" class="form-control" 
                                    placeholder="<?= lang('enter_filter_name') ?>" required>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i><?= lang('save') ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="itemsTableWrapper" class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                        <table id="itemsTable" class="table table-hover table-striped shadow-sm rounded overflow-hidden">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col"><?= Helper::renderSortHeader('id', '#', $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('image', lang('image'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('productname', lang('product_name'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('article_no', lang('article_no'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('manufacturer', lang('manufacturer'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('size', lang('size'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('color', lang('color'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('quantity', lang('quantity'), $filters) ?></th>
                                    <!-- <th scope="col"><?= Helper::renderSortHeader('grafted', lang('grafted'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('club', lang('club'), $filters) ?></th> -->
                                    <th scope="col"><?= Helper::renderSortHeader('expiry_date', lang('expiry_date'), $filters) ?></th>
                                    <th scope="col"><?= Helper::renderSortHeader('last_change', lang('last_change'), $filters) ?></th>
                                    <th scope="col"><?= lang('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <?php foreach ($items as $item): ?>
                                    <tr id="itemRow_<?= h($item['id']) ?>"
                                        class="clickable-row"
                                        data-edit-url="/edit/<?= h($item['id']) ?>"
                                        <?php if (!empty($item['description'])): ?>
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            data-bs-html="true"
                                            title="<strong>Description:</strong><br><?= h($item['description']) ?>"
                                        <?php endif; ?>
                                        style="cursor: pointer;">
                                        <th scope="row" id="itemId_<?= h($item['id']) ?>">
                                            <?= h($item['id']) ?>
                                        </th>
                                        <td id="itemImage_<?= h($item['id']) ?>">
                                            <?php if (!empty($item['img'])): ?>
                                                <img src="data:<?= h($item['mime_type'] ?? 'image/png') ?>;base64,<?= base64_encode($item['img']) ?>" 
                                                    alt="Product Image" class="img-thumbnail" style="max-width: 50px; height: auto;">
                                            <?php else: ?>
                                                <img src="/public/img/default-placeholder.png" alt="No Image" class="img-thumbnail" style="max-width: 50px; height: auto;">
                                            <?php endif; ?>
                                        </td>
                                        <td id="itemProductname_<?= h($item['id']) ?>"><?= h($item['productname']) ?></td>
                                        <td id="itemArticleNo_<?= h($item['id']) ?>"><?= h($item['article_no']) ?></td>
                                        <td id="itemManufacturer_<?= h($item['id']) ?>"><?= h($item['manufacturer']) ?></td>
                                        <td id="itemSize_<?= h($item['id']) ?>"><?= h($item['size']) ?></td>
                                        <td id="itemColor_<?= h($item['id']) ?>"><?= h($item['color']) ?></td>
                                        <td id="itemQuantity_<?= h($item['id']) ?>"><?= h($item['quantity']) ?></td>
                                        <!-- <td id="itemGrafted_<?= h($item['id']) ?>"><?= $item['grafted'] ? lang('yes') : lang('no') ?></td>
                                        <td id="itemClub_<?= h($item['id']) ?>"><?= h($item['club']) ?></td> -->
                                        <td id="itemExpiration_<?= h($item['id']) ?>"><?= h($item['expiry_date']) ?></td>
                                        <td>
                                            <?php
                                            if (!empty($item['last_change'])) {
                                                try {
                                                    $dt = new DateTime($item['last_change'], new DateTimeZone('UTC'));
                                                    $dt->setTimezone(new DateTimeZone($settings->getTimeZone()));
                                                    echo h($dt->format($settings->getDateFormat() . ' H:i:s'));
                                                } catch (Exception $e) {
                                                    echo h($item['last_change']);
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td id="itemActions_<?= h($item['id']) ?>" class="no-click">
                                            <div class="d-flex">
                                                <a href="/edit/<?= h($item['id']) ?>" class="btn btn-sm btn-warning me-2 nav-link-ajax" title="<?= lang('edit_item') ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                
                                                <form action="/delete/<?= h($item['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('<?= lang('confirm_delete_item') ?>');">
                                                    <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="<?= lang('delete_item') ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav id="paginationNav">
                            <ul class="pagination justify-content-center" id="paginationList">
                                <?php if ($page > 1): ?>
                                    <li class="page-item" id="prevPageItem">
                                        <a id="prevPageLink" class="page-link d-flex align-items-center pagination-link-ajax" 
                                           href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="bi bi-arrow-left-square-fill me-1"></i> <?= lang('previous') ?>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled" id="prevPageItemDisabled">
                                        <span id="prevPageSpan" class="page-link d-flex align-items-center">
                                            <i class="bi bi-arrow-left-square-fill me-1"></i> <?= lang('previous') ?>
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
                                            <a class="page-link pagination-link-ajax" id="pageLink-1" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>
                                        </li>';

                                if ($start > 2) {
                                    echo '<li class="page-item disabled" id="pageEllipsisStart"><span class="page-link">…</span></li>';
                                }

                                for ($i = $start; $i <= $end; $i++) {
                                    echo '<li class="page-item '.($i==$page?'active':'').'" id="pageItem-'.$i.'">
                                                <a class="page-link pagination-link-ajax" id="pageLink-'.$i.'" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>
                                            </li>';
                                }

                                if ($end < $totalPages - 1) {
                                    echo '<li class="page-item disabled" id="pageEllipsisEnd"><span class="page-link">…</span></li>';
                                }

                                if ($totalPages > 1) {
                                    echo '<li class="page-item '.($page==$totalPages?'active':'').'" id="pageItem-'.$totalPages.'">
                                                <a class="page-link pagination-link-ajax" id="pageLink-'.$totalPages.'" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a>
                                            </li>';
                                }
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item" id="nextPageItem">
                                        <a id="nextPageLink" class="page-link d-flex align-items-center pagination-link-ajax" 
                                           href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            <?= lang('next') ?> <i class="bi bi-arrow-right-square-fill ms-1"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled" id="nextPageItemDisabled">
                                        <span id="nextPageSpan" class="page-link d-flex align-items-center">
                                            <?= lang('next') ?> <i class="bi bi-arrow-right-square-fill ms-1"></i>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemListContainer = document.getElementById('item-list-container');
    const listMessageContainer = document.getElementById('list-message-container');
    
    /**
     * Initialize Bootstrap tooltips
     */
    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 500, hide: 100 },
                trigger: 'hover',
                html: true
            });
        });
    }

    /**
     * Shows a message to the user.
     */
    function showMessage(message, type) {
        const container = document.getElementById('list-message-container');
        if (!container) return;
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        container.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    /**
     * Loads the list of items via an AJAX request and updates the UI.
     */
    function loadItems(url) {
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            if (itemListContainer) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;

                const newContent = tempDiv.querySelector('.row.justify-content-center');
                if (newContent) {
                    const existingContent = itemListContainer.querySelector('.row.justify-content-center');
                    if (existingContent) {
                        existingContent.innerHTML = newContent.innerHTML;
                    } else {
                        itemListContainer.appendChild(newContent);
                    }
                } else {
                    itemListContainer.innerHTML = html;
                }
                
                initTooltips();
                history.pushState(null, '', url);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(error => {
            console.error('Error loading items:', error);
            showMessage('Failed to load items. Please try again.', 'danger');
        });
    }

    /**
     * Retrieves the current values from the filter form.
     */
    function getCurrentFilters() {
        const filterForm = document.getElementById('filterForm');
        if (!filterForm) return {};
        const formData = new FormData(filterForm);
        const filters = {};
        for (const [key, value] of formData.entries()) {
            if (value) {
                filters[key] = value;
            }
        }
        return filters;
    }

    // Handle clickable rows for editing
    document.addEventListener('click', function(e) {
        const row = e.target.closest('.clickable-row');
        
        if (row && !e.target.closest('.no-click')) {
            e.preventDefault();
            const editUrl = row.getAttribute('data-edit-url');
            if (editUrl) {
                window.location.href = editUrl;
            }
        }
    });

    // Initialize tooltips on page load
    initTooltips();

    // Add hover effect to clickable rows
    const style = document.createElement('style');
    style.textContent = `
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.1) !important;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        .no-click {
            pointer-events: auto !important;
        }
        .no-click * {
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(style);

    // ✅ USE EVENT DELEGATION for all dynamic content
    document.body.addEventListener('click', function(e) {
        const target = e.target;
        
        // Handle pagination and sorting links
        const paginationLink = target.closest('.pagination-link-ajax');
        const sortLink = target.closest('.sort-link-ajax');
        if (paginationLink || sortLink) {
            e.preventDefault();
            loadItems((paginationLink || sortLink).href);
            return;
        }

        // Handle delete item button
        const deleteItemBtn = target.closest('.delete-item-btn');
        if (deleteItemBtn) {
            e.preventDefault();
            const id = deleteItemBtn.dataset.id;
            if (confirm('Are you sure you want to delete this item?')) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                fetch(`/delete/${id}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById(`itemRow_${id}`);
                        if (row) row.remove();
                        showMessage('Item deleted successfully!', 'success');
                        loadItems(window.location.href);
                    } else {
                        showMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An unexpected error occurred.', 'danger');
                });
            }
            return;
        }

        // Handle clear filters button
        const clearFiltersBtn = target.closest('#clearFiltersBtn');
        if (clearFiltersBtn) {
            e.preventDefault();
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.reset();
            }
            loadItems('/list');
            return;
        }

        // Handle apply saved filter button
        const applySavedFilterBtn = target.closest('.apply-saved-filter');
        if (applySavedFilterBtn) {
            e.preventDefault();
            
            // Highlight the selected filter
            const allButtons = document.querySelectorAll('.apply-saved-filter');
            allButtons.forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            applySavedFilterBtn.classList.remove('btn-outline-primary');
            applySavedFilterBtn.classList.add('btn-primary');
            
            const filterId = applySavedFilterBtn.getAttribute('data-filter-id');
            const originalText = applySavedFilterBtn.innerHTML;
            applySavedFilterBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            applySavedFilterBtn.disabled = true;

            fetch(`/apply_saved_filter/${filterId}`, {
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
                    showMessage('Filter applied successfully', 'success');
                    setTimeout(() => window.location.href = data.redirect, 500);
                } else {
                    throw new Error(data.message || 'Failed to apply filter');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showMessage(error.message, 'danger');
                applySavedFilterBtn.innerHTML = originalText;
                applySavedFilterBtn.disabled = false;
                applySavedFilterBtn.classList.remove('btn-primary');
                applySavedFilterBtn.classList.add('btn-outline-primary');
            });
            return;
        }

        // Handle delete saved filter button
        const deleteFilterBtn = target.closest('.delete-saved-filter');
        if (deleteFilterBtn) {
            e.preventDefault();
            const filterId = deleteFilterBtn.dataset.filterId;
            if (confirm('Are you sure you want to delete this saved filter?')) {
                const originalText = deleteFilterBtn.innerHTML;
                deleteFilterBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                deleteFilterBtn.disabled = true;

                const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || 
                                 document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrfToken) {
                    showMessage('CSRF token missing. Please refresh the page.', 'danger');
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
                        showMessage('Filter deleted successfully', 'success');
                        const filterElement = document.getElementById(`savedFilter_${filterId}`);
                        if (filterElement) {
                            filterElement.remove();
                        }
                        
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
                    showMessage(error.message, 'danger');
                    deleteFilterBtn.innerHTML = originalText;
                    deleteFilterBtn.disabled = false;
                });
            }
            return;
        }
    });

    // ✅ USE EVENT DELEGATION for filter form submit
    document.body.addEventListener('submit', function(e) {
        // Handle filter form submission
        if (e.target && e.target.id === 'filterForm') {
            e.preventDefault();
            const formData = new FormData(e.target);
            const queryString = new URLSearchParams(formData).toString();
            const url = '/list' + (queryString ? '?' + queryString : '');
            loadItems(url);
            return;
        }

        // ✅ Handle save filter form submission with EVENT DELEGATION
        if (e.target && e.target.id === 'saveFilterForm') {
            e.preventDefault();
            const formData = new FormData(e.target);
            const currentFilters = getCurrentFilters();
            
            formData.append('source_page', 'list');
            
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
                    showMessage(data.message, 'success');
                    e.target.reset();
                    // Reload the page to show the new saved filter button
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Failed to save filter', 'danger');
            });
            return;
        }
    });
});
</script>