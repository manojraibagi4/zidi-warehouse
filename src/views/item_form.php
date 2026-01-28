<?php
// src/views/item_form.php
$is_editing = isset($item->id) && $item->id > 0;
$form_action = $is_editing ? 'update' : 'store';
$page_title = $is_editing ? lang('edit_item') : lang('add_new_item');

$productname = htmlspecialchars($item->productname ?? '');
$manufacturer = htmlspecialchars($item->manufacturer ?? '');
$description = htmlspecialchars($item->description ?? '');
$size = htmlspecialchars($item->size ?? '');
$color = htmlspecialchars($item->color ?? '');
$quantity = htmlspecialchars($item->quantity ?? 1);
$grafted_checked = ($item->grafted ?? 0) ? 'checked' : '';
$club = htmlspecialchars($item->club ?? '');

$item_id = htmlspecialchars($item->id ?? '');

// This part of the image logic seems a bit off, it's better to store the image as a file path.
// The base64 encoding is more for small, inline images. Assuming your database stores binary data,
// your logic for fetching it seems correct, but it's not a common pattern.
$current_image = '/public/img/default-placeholder.png';
if ($is_editing && !empty($item->img)) {
    $mime_type = $item->mime_type ?? 'image/png';
    $base64 = base64_encode($item->img);
    $current_image = "data:$mime_type;base64,$base64";
}

// Get dropdown data from settingsRepo (passed from controller)
$sizes = $settingsRepo->getSizes();
$clubs = $settingsRepo->getClubs();
$manufacturers = $settingsRepo->getManufacturers();
$categories = $settingsRepo->getCategories();
$suppliers = $settingsRepo->getSuppliers();
?>

<div class="container-fluid py-4" id="itemFormContainer">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center rounded-top">
                    <h2 class="mb-0 py-2" id="itemFormTitle">
                        <i class="bi <?= $is_editing ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i><?= $page_title ?>
                    </h2>
                </div>
                <div class="card-body p-4">
                    <div id="response-message"></div>
                    <form id="itemForm" action="/<?= $form_action ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                        <?php if ($is_editing): ?>
                            <input type="hidden" name="id" id="item_id" value="<?= $item_id ?>">
                        <?php endif; ?>
                        
                        <!-- Product Name & Manufacturer -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="productname" class="form-label">
                                    <i class="bi bi-tag me-2"></i><?= lang('product_name') ?> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="productname" name="productname" value="<?= $productname ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="manufacturer" class="form-label">
                                    <i class="bi bi-building me-2"></i><?= lang('manufacturer') ?> <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="manufacturer" name="manufacturer" required>
                                    <option value=""><?= lang('select_manufacturer') ?></option>
                                    <?php foreach ($manufacturers as $manufacturer_item): ?>
                                        <option value="<?= htmlspecialchars($manufacturer_item['name']) ?>" 
                                            <?= ($manufacturer === $manufacturer_item['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($manufacturer_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= lang('cant_find_manufacturer') ?> 
                                        <a href="/settings#product-settings" target="_blank" class="text-primary">
                                            <?= lang('add_in_settings') ?>
                                        </a>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="description" class="form-label">
                                    <i class="bi bi-card-text me-2"></i><?= lang('description') ?>
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= $description ?></textarea>
                            </div>
                        </div>

                        <!-- Quantity, Unit Price & Total Price -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">
                                    <i class="bi bi-boxes me-2"></i><?= lang('quantity') ?> <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?= $quantity ?>" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label for="unit_price" class="form-label">
                                    <i class="bi bi-currency-euro me-2"></i><?= lang('unit_price') ?>
                                </label>
                                <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" 
                                    value="<?= htmlspecialchars($item->unit_price ?? '') ?>" min="0">
                            </div>

                            <div class="col-md-4">
                                <label for="total_price" class="form-label">
                                    <i class="bi bi-cash-stack me-2"></i><?= lang('total_price') ?>
                                </label>
                                <input type="number" step="0.01" class="form-control" id="total_price" name="total_price" 
                                    value="<?= htmlspecialchars($item->total_price ?? '') ?>" min="0" readonly>
                                <small class="form-text text-muted"><?= lang('auto_calculated') ?></small>
                            </div>
                        </div>

                        <!-- Size & Color -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="size" class="form-label">
                                    <i class="bi bi-rulers me-2"></i><?= lang('size') ?>
                                </label>
                                <select class="form-select" id="size" name="size">
                                    <option value=""><?= lang('select_size') ?></option>
                                    <?php foreach ($sizes as $size_item): ?>
                                        <option value="<?= htmlspecialchars($size_item['name']) ?>" 
                                            <?= ($size === $size_item['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($size_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= lang('cant_find_size') ?> 
                                        <a href="/settings#product-settings" target="_blank" class="text-primary">
                                            <?= lang('add_in_settings') ?>
                                        </a>
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="color" class="form-label">
                                    <i class="bi bi-palette me-2"></i><?= lang('color') ?>
                                </label>
                                <input type="text" class="form-control" id="color" name="color" value="<?= $color ?>">
                            </div>
                        </div>

                        <!-- Category & Article No -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">
                                    <i class="bi bi-tags me-2"></i><?= lang('category') ?>
                                </label>
                                <select class="form-select" id="category" name="category">
                                    <option value=""><?= lang('select_category') ?></option>
                                    <?php 
                                    $current_category = htmlspecialchars($item->category ?? '');
                                    foreach ($categories as $category_item): ?>
                                        <option value="<?= htmlspecialchars($category_item['name']) ?>" 
                                            <?= ($current_category === $category_item['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= lang('cant_find_category') ?> 
                                        <a href="/settings#product-settings" target="_blank" class="text-primary">
                                            <?= lang('add_in_settings') ?>
                                        </a>
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="article_no" class="form-label">
                                    <i class="bi bi-upc-scan me-2"></i><?= lang('article_no') ?>
                                </label>
                                <input type="text" class="form-control" id="article_no" name="article_no" 
                                    value="<?= htmlspecialchars($item->article_no ?? '') ?>">
                            </div>
                        </div>

                        <!-- Color Number & Expiry Date -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="color_number" class="form-label">
                                    <i class="bi bi-palette-fill me-2"></i><?= lang('color_number') ?>
                                </label>
                                <input type="text" class="form-control" id="color_number" name="color_number" 
                                    value="<?= htmlspecialchars($item->color_number ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="expiry_date" class="form-label">
                                    <i class="bi bi-calendar-event me-2"></i><?= lang('expiry_date') ?>
                                </label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                    value="<?= htmlspecialchars($item->expiry_date ?? '') ?>">
                            </div>
                        </div>

                        <!-- Supplier -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplier" class="form-label">
                                    <i class="bi bi-truck me-2"></i><?= lang('supplier') ?>
                                </label>
                                <select class="form-select" id="supplier" name="supplier">
                                    <option value=""><?= lang('select_supplier') ?></option>
                                    <?php 
                                    $current_supplier = htmlspecialchars($item->supplier ?? '');
                                    foreach ($suppliers as $supplier_item): ?>
                                        <option value="<?= htmlspecialchars($supplier_item['name']) ?>" 
                                            <?= ($current_supplier === $supplier_item['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($supplier_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= lang('cant_find_supplier') ?> 
                                        <a href="/settings#product-settings" target="_blank" class="text-primary">
                                            <?= lang('add_in_settings') ?>
                                        </a>
                                    </small>
                                </div>
                            </div>

                            
                        </div>

                        <!-- Grafted Checkbox -->
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="grafted" name="grafted" value="1" <?= $grafted_checked ?>>
                                    <label class="form-check-label" for="grafted">
                                        <i class="bi bi-patch-check me-2"></i><?= lang('grafted') ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Club Field (Conditional) -->
                        <div class="row g-3" id="clubField" style="<?= $grafted_checked ? '' : 'display:none;' ?>">
                            <div class="col-12">
                                <label for="club" class="form-label">
                                    <i class="bi bi-journal me-2"></i><?= lang('club_name') ?>
                                </label>
                                <select class="form-select" id="club" name="club">
                                    <option value=""><?= lang('select_club') ?></option>
                                    <?php foreach ($clubs as $club_item): ?>
                                        <option value="<?= htmlspecialchars($club_item['name']) ?>" 
                                            <?= ($club === $club_item['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($club_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= lang('cant_find_club') ?> 
                                        <a href="/settings#product-settings" target="_blank" class="text-primary">
                                            <?= lang('add_in_settings') ?>
                                        </a>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Product Image -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="img" class="form-label">
                                    <i class="bi bi-image me-2"></i><?= lang('product_image') ?>
                                </label>
                                <input class="form-control" type="file" id="img" name="img" accept="image/*">
                                <small class="form-text text-muted"><?= lang('image_help_text') ?></small>
                                <div class="mt-2" id="imagePreviewContainer">
                                    <p class="text-muted" id="imagePreviewText"><?= $is_editing ? lang('current_image') : lang('no_image_selected') ?></p>
                                    <img src="<?= $current_image ?>" alt="Product Image Preview" class="img-thumbnail mt-2" style="max-width: 150px; height: auto;" id="currentImage">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-3">
                            <div class="col-12 d-flex justify-content-end">
                                <a href="/list" class="btn btn-secondary btn-lg me-2">
                                    <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                </a>
                                <?php if ($is_editing): ?>
                                    <button type="button" id="duplicateBtn" class="btn btn-info btn-lg me-2">
                                        <i class="bi bi-files me-2"></i><?= lang('duplicate') ?>
                                    </button>
                                <?php endif; ?>
                                <button type="submit" id="itemSubmitBtn" class="btn btn-<?= $is_editing ? 'primary' : 'success' ?> btn-lg">
                                    <i class="bi bi-floppy me-2"></i><?= $is_editing ? lang('update_item') : lang('add_item') ?>
                                </button>
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
    const graftedCheckbox = document.getElementById('grafted');
    const clubField = document.getElementById('clubField');
    const clubInput = document.getElementById('club');
    const form = document.getElementById('itemForm');
    const responseMessageContainer = document.getElementById('response-message');
    const submitButton = document.getElementById('itemSubmitBtn');
    const duplicateButton = document.getElementById('duplicateBtn');
    const imgInput = document.getElementById('img');
    const imagePreview = document.getElementById('currentImage');
    const imagePreviewText = document.getElementById('imagePreviewText');
    const defaultPlaceholder = '/public/img/default-placeholder.png';
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalPriceInput = document.getElementById('total_price');

    // Calculate total price
    function calculateTotalPrice() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const totalPrice = (quantity * unitPrice).toFixed(2);
        totalPriceInput.value = totalPrice;
    }

    quantityInput.addEventListener('input', calculateTotalPrice);
    unitPriceInput.addEventListener('input', calculateTotalPrice);
    calculateTotalPrice();

    // Show/hide club field and clear club value when grafted is unchecked
    graftedCheckbox.addEventListener('change', function() {
        if (this.checked) {
            clubField.style.display = 'block';
        } else {
            clubField.style.display = 'none';
            clubInput.value = ''; // Clear the club value when unchecked
        }
    });

    // Duplicate button functionality
    if (duplicateButton) {
        duplicateButton.addEventListener('click', async function() {
            // Gather all current form values
            const formData = {
                productname: document.getElementById('productname').value,
                manufacturer: document.getElementById('manufacturer').value,
                description: document.getElementById('description').value,
                quantity: document.getElementById('quantity').value,
                unit_price: document.getElementById('unit_price').value,
                total_price: document.getElementById('total_price').value,
                size: document.getElementById('size').value,
                color: document.getElementById('color').value,
                category: document.getElementById('category').value,
                article_no: document.getElementById('article_no').value,
                color_number: document.getElementById('color_number').value,
                expiry_date: document.getElementById('expiry_date').value,
                supplier: document.getElementById('supplier').value,
                grafted: graftedCheckbox.checked ? '1' : '0',
                club: document.getElementById('club').value,
                current_image: imagePreview.src !== defaultPlaceholder ? imagePreview.src : null
            };

            // Store in sessionStorage
            sessionStorage.setItem('duplicateItemData', JSON.stringify(formData));
            
            // Redirect to create page
            window.location.href = '/create';
        });
    }

    // Check if we're duplicating an item
    const duplicateData = sessionStorage.getItem('duplicateItemData');
    if (duplicateData && !<?= json_encode($is_editing) ?>) {
        const data = JSON.parse(duplicateData);
        
        // Populate all fields
        document.getElementById('productname').value = data.productname + ' (Copy)';
        document.getElementById('manufacturer').value = data.manufacturer;
        document.getElementById('description').value = data.description;
        document.getElementById('quantity').value = data.quantity;
        document.getElementById('unit_price').value = data.unit_price;
        document.getElementById('total_price').value = data.total_price;
        document.getElementById('size').value = data.size;
        document.getElementById('color').value = data.color;
        document.getElementById('category').value = data.category;
        document.getElementById('article_no').value = data.article_no;
        document.getElementById('color_number').value = data.color_number;
        document.getElementById('expiry_date').value = data.expiry_date;
        document.getElementById('supplier').value = data.supplier;
        
        // Handle grafted checkbox and club field
        if (data.grafted === '1') {
            graftedCheckbox.checked = true;
            clubField.style.display = 'block';
            document.getElementById('club').value = data.club;
        }
        
        // Handle image duplication
        if (data.current_image && data.current_image !== defaultPlaceholder) {
            imagePreview.src = data.current_image;
            imagePreviewText.textContent = '<?= lang('duplicated_image') ?>';
            
            // Convert base64 to File object and attach to file input
            convertBase64ToFile(data.current_image, 'duplicated-image.png').then(file => {
                // Create a DataTransfer object to set the file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                imgInput.files = dataTransfer.files;
            }).catch(err => {
                console.error('Error converting image:', err);
                imagePreview.src = defaultPlaceholder;
                imagePreviewText.textContent = '<?= lang('no_image_selected') ?>';
            });
        } else {
            imagePreview.src = defaultPlaceholder;
            imagePreviewText.textContent = '<?= lang('no_image_selected') ?>';
        }
        
        // Clear sessionStorage
        sessionStorage.removeItem('duplicateItemData');
        
        // Show success message
        showMessage('<?= lang('item_duplicated_message') ?>', 'info');
    }

    // Helper function to convert base64 to File object
    async function convertBase64ToFile(base64String, filename) {
        // Fetch the base64 string as a blob
        const response = await fetch(base64String);
        const blob = await response.blob();
        
        // Create a File object from the blob
        const file = new File([blob], filename, { type: blob.type });
        return file;
    }

    // Image preview
    imgInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Invalid image type. Please select JPEG, PNG, GIF, or WebP.', 'danger');
                this.value = ''; // Clear the input
                return;
            }

            // Validate file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                showMessage('Image size exceeds 5MB. Please select a smaller image.', 'danger');
                this.value = ''; // Clear the input
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewText.textContent = '<?= lang('new_image_preview') ?>';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = defaultPlaceholder;
            imagePreviewText.textContent = '<?= $is_editing ? lang('current_image') : lang('no_image_selected') ?>';
        }
    });
    
    // Form submission with proper FormData handling
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= $is_editing ? lang('updating') : lang('adding') ?>...';

        // Create FormData from the form
        const formData = new FormData(form);
        
        // Ensure grafted checkbox value is properly set
        if (!graftedCheckbox.checked) {
            formData.delete('grafted'); // Remove grafted if unchecked
            formData.delete('club'); // Remove club if grafted is not checked
        }

        // Debug: Log FormData contents (remove in production)
        console.log('FormData contents:');
        for (let pair of formData.entries()) {
            if (pair[0] === 'img') {
                console.log(pair[0] + ': [File object]', pair[1]);
            } else {
                console.log(pair[0] + ': ' + pair[1]);
            }
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (response.ok && result.success) {
                showMessage(result.message, 'success');
                
                if (!<?= json_encode($is_editing) ?>) {
                    form.reset();
                    imagePreview.src = defaultPlaceholder;
                    imagePreviewText.textContent = '<?= lang('no_image_selected') ?>';
                    document.getElementById('manufacturer').selectedIndex = 0;
                    document.getElementById('size').selectedIndex = 0;
                    document.getElementById('club').selectedIndex = 0;
                    clubField.style.display = 'none';
                }
                
                setTimeout(() => {
                    window.location.href = '/list';
                }, 2000);
            } else {
                throw new Error(result.message || 'Failed to process request.');
            }
        } catch (err) {
            console.error('Error:', err);
            showMessage(err.message || 'An unexpected error occurred.', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-floppy me-2"></i><?= $is_editing ? lang('update_item') : lang('add_item') ?>';
        }
    });
    
    function showMessage(message, type) {
        responseMessageContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        
        // Auto-scroll to message
        responseMessageContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
});
</script>