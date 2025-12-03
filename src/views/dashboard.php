<?php
// src/views/dashboard.php
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center rounded-top">
                    <h2 class="mb-0 py-2" id="dashboardTitle">
                        <i class="bi bi-speedometer2 me-2"></i> <?= lang('dashboard_overview') ?>
                    </h2>
                </div>
                <div class="card-body p-4">
                    <p class="lead text-muted" id="dashboardWelcome"><?= lang('dashboard_welcome') ?></p>
    
                    <hr class="my-4">
    
                    <div class="row mt-4 d-flex align-items-stretch" id="dashboardStatsRow">
                        <div class="col-md-6 mb-4" id="totalItemsCardCol">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title text-muted" id="totalItemsTitle"><i class="bi bi-tag me-2"></i><?= lang('total_items') ?></h5>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h2 class="card-text fs-2 mb-0" id="total-items-count">...</h2>
                                            <i class="bi bi-boxes text-primary" style="font-size: 3rem;"></i>
                                        </div>
                                    </div>
                                    <a href="/list" class="btn btn-primary mt-3" id="viewAllItemsBtn">
                                        <i class="bi bi-eye-fill me-2"></i><?= lang('view_all_items') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
    
                        <div class="col-md-6 mb-4" id="lowStockCardCol">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title text-muted" id="lowStockTitle"><i class="bi bi-exclamation-triangle me-2"></i><?= lang('items_low_in_stock') ?></h5>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h2 class="card-text fs-2 mb-0 text-warning" id="low-stock-count">...</h2>
                                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                        </div>
                                        <p class="card-text text-muted mt-2">
                                            <small><?= lang('threshold') ?>: <span id="low-stock-threshold-value">...</span></small>
                                        </p>
                                    </div>
                                    <a href="/viewLessStock" class="btn btn-warning mt-3 nav-link-ajax" id="viewLowStockBtn">
                                        <i class="bi bi-list-ol me-2"></i><?= lang('view_low_stock') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <?php if (!empty($_SESSION['role']) && ($_SESSION['role'] === 'Administrator' || $_SESSION['role'] === 'Warehouse')): ?>
                        <div class="row mt-4" id="quickActionsRow">
                            <div class="col-12">
                                <h3 class="mb-3" id="quickActionsTitle"><i class="bi bi-lightning-fill me-2"></i><?= lang('quick_actions') ?></h3>
                                <div class="list-group shadow-sm" id="quickActionsList">
                                    <!-- Article Number Search Section -->
                                    <?php if (!empty($_SESSION['role']) && ($_SESSION['role'] === 'Administrator' || $_SESSION['role'] === 'Warehouse')): ?>
                                        <div class="row mt-4" id="articleSearchRow">
                                            <div class="col-12">
                                                <div class="card shadow-sm border-0">
                                                    <div class="card-body">
                                                        <h3 class="mb-3"><i class="bi bi-search me-2"></i><?= lang('search_by_article_number') ?></h3>
                                                        <div class="row g-3">
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control form-control-lg" id="article-search-input" placeholder="<?= lang('enter_article_number') ?>" autocomplete="off">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-primary btn-lg w-100" id="article-search-btn">
                                                                    <i class="bi bi-search me-2"></i><?= lang('search') ?>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Search Results -->
                                                        <div id="article-search-results" class="mt-4" style="display: none;">
                                                            <div class="alert alert-info" id="article-result-info"></div>
                                                            <div id="article-result-details"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($_SESSION['role']) && ($_SESSION['role'] === 'Administrator' || $_SESSION['role'] === 'Warehouse')): ?>
                                        <a href="/create" class="list-group-item list-group-item-action py-3" id="addNewItemLink">
                                        <i class="bi bi-plus-circle me-2"></i> <?= lang('add_new_item') ?>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
                                        <a href="/import" class="list-group-item list-group-item-action py-3" id="importDataLink">
                                            <i class="bi bi-cloud-arrow-up me-2"></i> <?= lang('import_data') ?>
                                        </a>
                                        <a href="/export" class="list-group-item list-group-item-action py-3" id="exportDataLink">
                                            <i class="bi bi-cloud-arrow-down me-2"></i> <?= lang('export_data') ?>
                                        </a>
                                        <a href="/signup" class="list-group-item list-group-item-action py-3" id="signupNewUserLink">
                                            <i class="bi bi-person-plus me-2"></i> <?= lang('sign_up_new_user') ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass translations to JavaScript
const translations = {
    searching: <?= json_encode(lang('searching')) ?>,
    foundItems: <?= json_encode(lang('found_items')) ?>,
    noItemsFound: <?= json_encode(lang('no_items_found_article')) ?>,
    errorSearching: <?= json_encode(lang('error_searching_article')) ?>,
    articleNo: <?= json_encode(lang('article_no')) ?>,
    productName: <?= json_encode(lang('product_name')) ?>,
    manufacturer: <?= json_encode(lang('manufacturer')) ?>,
    size: <?= json_encode(lang('size')) ?>,
    color: <?= json_encode(lang('color')) ?>,
    quantity: <?= json_encode(lang('quantity')) ?>,
    action: <?= json_encode(lang('action')) ?>,
    update: <?= json_encode(lang('update')) ?>,
    view: <?= json_encode(lang('view')) ?>,
    na: <?= json_encode(lang('na')) ?>,
    pleaseEnterArticleNumber: <?= json_encode(lang('please_enter_article_number')) ?>,
    pleaseEnterValidQuantity: <?= json_encode(lang('please_enter_valid_quantity')) ?>,
    quantityUpdatedSuccessfully: <?= json_encode(lang('quantity_updated_successfully')) ?>,
    errorUpdatingQuantity: <?= json_encode(lang('error_updating_quantity')) ?>,
    item: <?= json_encode(lang('item')) ?>,
    items: <?= json_encode(lang('items')) ?>,
    newQuantity: <?= json_encode(lang('new_quantity')) ?>,
    cancel: <?= json_encode(lang('cancel')) ?>,
    save: <?= json_encode(lang('save')) ?>
};

document.addEventListener('DOMContentLoaded', function() {
    function fetchDashboardData() {
        fetch('/dashboard', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const dashboardData = data.data;

                const totalItemsElement = document.getElementById('total-items-count');
                if (totalItemsElement) {
                    totalItemsElement.textContent = dashboardData.totalItems;
                }

                const lowStockCountElement = document.getElementById('low-stock-count');
                if (lowStockCountElement) {
                    lowStockCountElement.textContent = dashboardData.lowStockItems;
                }

                const thresholdValueElement = document.getElementById('low-stock-threshold-value');
                if (thresholdValueElement) {
                    thresholdValueElement.textContent = dashboardData.lowStockThreshold;
                }
            } else {
                console.error('API Error:', data.error);
                document.getElementById('total-items-count').textContent = 'Error';
                document.getElementById('low-stock-count').textContent = 'Error';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('total-items-count').textContent = 'Failed to load';
            document.getElementById('low-stock-count').textContent = 'Failed to load';
            document.getElementById('low-stock-threshold-value').textContent = 'Failed to load';
        });
    }
    
    fetchDashboardData();

    // Article Number Search Functionality
    const articleSearchInput = document.getElementById('article-search-input');
    const articleSearchBtn = document.getElementById('article-search-btn');
    const articleSearchResults = document.getElementById('article-search-results');
    const articleResultInfo = document.getElementById('article-result-info');
    const articleResultDetails = document.getElementById('article-result-details');

    // Search on button click
    if (articleSearchBtn) {
        articleSearchBtn.addEventListener('click', performArticleSearch);
    }

    // Search on Enter key
    if (articleSearchInput) {
        articleSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performArticleSearch();
            }
        });
    }

    function performArticleSearch() {
        const articleNo = articleSearchInput.value.trim();
        
        if (!articleNo) {
            alert(translations.pleaseEnterArticleNumber);
            return;
        }

        // Show loading state
        articleResultInfo.textContent = translations.searching;
        articleResultInfo.className = 'alert alert-info';
        articleSearchResults.style.display = 'block';
        articleResultDetails.innerHTML = '';

        fetch('/search_article', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ article_no: articleNo })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items && data.items.length > 0) {
                const itemCount = data.items.length;
                const itemWord = itemCount === 1 ? translations.item : translations.items;
                articleResultInfo.textContent = `${translations.foundItems} ${itemCount} ${itemWord}`;
                articleResultInfo.className = 'alert alert-success';
                
                let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
                html += `<thead><tr>
                    <th>${translations.articleNo}</th>
                    <th>${translations.productName}</th>
                    <th>${translations.manufacturer}</th>
                    <th>${translations.size}</th>
                    <th>${translations.color}</th>
                    <th>${translations.quantity}</th>
                    <th>${translations.action}</th>
                </tr></thead><tbody>`;
                
                data.items.forEach(item => {
                    const quantityClass = item.quantity < 10 ? 'text-danger fw-bold' : '';
                    html += `<tr id="item-row-${item.id}">
                        <td>${item.article_no || translations.na}</td>
                        <td>${item.productname}</td>
                        <td>${item.manufacturer || translations.na}</td>
                        <td>${item.size || translations.na}</td>
                        <td>${item.color || translations.na}</td>
                        <td class="${quantityClass}" id="qty-display-${item.id}">${item.quantity}</td>
                        <td>
                            <div id="action-buttons-${item.id}">
                                <button class="btn btn-sm btn-primary update-qty-btn" 
                                    onclick="showInlineEdit(${item.id}, ${item.quantity})">
                                    <i class="bi bi-pencil-square me-1"></i>${translations.update}
                                </button>
                                <a href="/edit/${item.id}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-eye me-1"></i>${translations.view}
                                </a>
                            </div>
                            <div id="edit-form-${item.id}" style="display: none;">
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="new-qty-${item.id}" 
                                        value="${item.quantity}" min="0" style="max-width: 100px;">
                                    <button class="btn btn-success" onclick="saveQuantity(${item.id})">
                                        <i class="bi bi-check-lg"></i> ${translations.save}
                                    </button>
                                    <button class="btn btn-secondary" onclick="cancelEdit(${item.id}, ${item.quantity})">
                                        <i class="bi bi-x-lg"></i> ${translations.cancel}
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                articleResultDetails.innerHTML = html;
            } else {
                articleResultInfo.textContent = translations.noItemsFound;
                articleResultInfo.className = 'alert alert-warning';
                articleResultDetails.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            articleResultInfo.textContent = translations.errorSearching;
            articleResultInfo.className = 'alert alert-danger';
            articleResultDetails.innerHTML = '';
        });
    }

    // Make functions global so they can be called from inline HTML
    window.showInlineEdit = function(itemId, currentQty) {
        document.getElementById(`action-buttons-${itemId}`).style.display = 'none';
        document.getElementById(`edit-form-${itemId}`).style.display = 'block';
        document.getElementById(`new-qty-${itemId}`).focus();
    };

    window.cancelEdit = function(itemId, originalQty) {
        document.getElementById(`action-buttons-${itemId}`).style.display = 'block';
        document.getElementById(`edit-form-${itemId}`).style.display = 'none';
        document.getElementById(`new-qty-${itemId}`).value = originalQty;
    };

    window.saveQuantity = function(itemId) {
        const newQuantity = document.getElementById(`new-qty-${itemId}`).value;
        
        if (!newQuantity || newQuantity < 0) {
            alert(translations.pleaseEnterValidQuantity);
            return;
        }

        // Disable the save button to prevent double-clicks
        const saveBtn = event.target;
        saveBtn.disabled = true;

        fetch('/update_quantity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                item_id: itemId, 
                quantity: parseInt(newQuantity)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the quantity display
                const qtyDisplay = document.getElementById(`qty-display-${itemId}`);
                qtyDisplay.textContent = newQuantity;
                
                // Update styling based on new quantity
                if (parseInt(newQuantity) < 10) {
                    qtyDisplay.className = 'text-danger fw-bold';
                } else {
                    qtyDisplay.className = '';
                }
                
                // Hide edit form and show buttons again
                document.getElementById(`action-buttons-${itemId}`).style.display = 'block';
                document.getElementById(`edit-form-${itemId}`).style.display = 'none';
                
                // Show success message
                alert(translations.quantityUpdatedSuccessfully);
                
                // Refresh dashboard stats
                fetchDashboardData();
            } else {
                alert(translations.errorUpdatingQuantity + ': ' + (data.message || ''));
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            alert(translations.errorUpdatingQuantity);
            saveBtn.disabled = false;
        });
    };
});
</script>