<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/lang.php';
?>

<div class="container-fluid py-4" id="settings_container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white rounded-top">
                    <h1 class="mb-0 py-2 text-center" id="settings_title">
                        <i class="bi bi-gear me-2"></i><?= lang('settings_page') ?>
                    </h1>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert" id="settings_message">
                            <?= htmlspecialchars($_SESSION['message']['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <div id="ajax_message_container"></div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="app-settings-tab" data-bs-toggle="tab" 
                                    data-bs-target="#app-settings" type="button" role="tab" 
                                    aria-controls="app-settings" aria-selected="true">
                                <i class="bi bi-app-indicator me-2"></i><?= lang('app_settings') ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="product-settings-tab" data-bs-toggle="tab" 
                                    data-bs-target="#product-settings" type="button" role="tab" 
                                    aria-controls="product-settings" aria-selected="false">
                                <i class="bi bi-box-seam me-2"></i><?= lang('product_settings') ?>
                            </button>
                        </li>
                         <li class="nav-item" role="presentation">
                            <button class="nav-link" id="database-settings-tab" data-bs-toggle="tab" 
                                    data-bs-target="#database-settings" type="button" role="tab" 
                                    aria-controls="database-settings" aria-selected="false">
                                <i class="bi bi-database me-2"></i><?= lang('database_settings') ?>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- App Settings Tab -->
                        <div class="tab-pane fade show active" id="app-settings" role="tabpanel" 
                             aria-labelledby="app-settings-tab" tabindex="0">
                            <form id="settings_form">
                                <input type="hidden" name="csrf_token" id="csrf_token" value="<?= generateCsrfToken(); ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="lowstock_threshold" class="form-label">
                                            <i class="bi bi-bar-chart-line me-2"></i><?= lang('lowstock_threshold') ?>
                                        </label>
                                        <input type="number" id="lowstock_threshold" name="lowstock_threshold"
                                                class="form-control"
                                                value="<?= htmlspecialchars($settings->getLowstockThreshold()) ?>"
                                                required>
                                    </div>

                                    <!-- Expiry Days Input -->
                                    <div class="col-md-6">
                                        <label for="expiry_days" class="form-label">
                                            <i class="bi bi-calendar-x me-2"></i><?= lang('expiry_days') ?>
                                        </label>
                                        <input type="number" id="expiry_days" name="expiry_days"
                                                class="form-control"
                                                value="<?= htmlspecialchars($settings->getExpiryDays()) ?>"
                                                min="1"
                                                required>
                                        <small class="form-text text-muted"><?= lang('expiry_days_help') ?></small>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="default_lang" class="form-label">
                                            <i class="bi bi-translate me-2"></i><?= lang('default_lang') ?>
                                        </label>
                                        <select id="default_lang" name="default_lang" class="form-select">
                                            <option value="en" <?= $settings->getDefaultLang() === 'en' ? 'selected' : '' ?>>English</option>
                                            <option value="de" <?= $settings->getDefaultLang() === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="header" class="form-label">
                                        <i class="bi me-2"></i><?= lang('header') ?>
                                    </label>
                                    <input type="text" id="header" name="header"
                                            class="form-control"
                                            value="<?= htmlspecialchars($settings->getHeader()) ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="footer" class="form-label">
                                        <i class="bi me-2"></i><?= lang('footer') ?>
                                    </label>
                                    <input type="text" id="footer" name="footer"
                                            class="form-control"
                                            value="<?= htmlspecialchars($settings->getFooter()) ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="from_email" class="form-label">
                                        <i class="bi bi-envelope me-2"></i><?= lang('from_email') ?>
                                    </label>
                                    <input type="email" id="from_email" name="from_email"
                                            class="form-control"
                                            value="<?= htmlspecialchars($settings->getFromEmail()) ?>"
                                            >
                                </div>

                                <div class="mb-3">
                                    <label for="app_password" class="form-label">
                                        <i class="bi bi-key-fill me-2"></i><?= lang('app_password') ?>
                                    </label>
                                    <input type="password" id="app_password" name="app_password" autocomplete="current-password"
                                            class="form-control"
                                            value="<?= htmlspecialchars($settings->getAppPassword()) ?>"
                                            >
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="date_format" class="form-label">
                                            <i class="bi bi-calendar me-2"></i><?= lang('date_format') ?>
                                        </label>
                                        <select id="date_format" name="date_format" class="form-select" required>
                                            <?php
                                            $dateFormats = ['Y-m-d', 'd-m-Y', 'm/d/Y', 'd M Y'];
                                            foreach ($dateFormats as $format): ?>
                                                <option value="<?= $format ?>" <?= $settings->getDateFormat() === $format ? 'selected' : '' ?>>
                                                    <?= date($format) ?> (<?= $format ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="time_zone" class="form-label">
                                            <i class="bi bi-clock me-2"></i><?= lang('time_zone') ?>
                                        </label>
                                        <select id="time_zone" name="time_zone" class="form-select" required>
                                            <?php
                                            $timezones = DateTimeZone::listIdentifiers();
                                            foreach ($timezones as $tz): ?>
                                                <option value="<?= $tz ?>" <?= $settings->getTimeZone() === $tz ? 'selected' : '' ?>>
                                                    <?= $tz ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary me-2" id="save_settings_btn">
                                        <i class="bi bi-save me-2"></i><?= lang('save_settings') ?>
                                    </button>
                                    <a href="/dashboard" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                    </a>
                                </div>
                            </form>

                            
                        </div>

                                                <!-- Product Settings Tab -->
                        <div class="tab-pane fade" id="product-settings" role="tabpanel" 
                             aria-labelledby="product-settings-tab" tabindex="0">
                            
                            <!-- Sizes Management -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#sizesCollapse">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-rulers me-2"></i><?= lang('manage_sizes') ?>
                                    </h5>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                                <div id="sizesCollapse" class="collapse show">
                                    <div class="card-body">
                                        <form id="size_form" class="mb-4">
                                            <input type="hidden" name="size_id" id="size_id" value="">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label for="size_name" class="form-label"><?= lang('size_name') ?></label>
                                                    <input type="text" id="size_name" name="size_name" 
                                                           class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-success" id="add_size_btn">
                                                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_size') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancel_size_btn" style="display: none;">
                                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="sizes_table">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('size_name') ?></th>
                                                        <th width="150"><?= lang('actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sizes_list">
                                                    <!-- Sizes will be loaded here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clubs Management -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#clubsCollapse">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-people me-2"></i><?= lang('manage_clubs') ?>
                                    </h5>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                                <div id="clubsCollapse" class="collapse">
                                    <div class="card-body">
                                        <form id="club_form" class="mb-4">
                                            <input type="hidden" name="club_id" id="club_id" value="">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label for="club_name" class="form-label"><?= lang('club_name') ?></label>
                                                    <input type="text" id="club_name" name="club_name" 
                                                           class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-success" id="add_club_btn">
                                                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_club') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancel_club_btn" style="display: none;">
                                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="clubs_table">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('club_name') ?></th>
                                                        <th width="150"><?= lang('actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="clubs_list">
                                                    <!-- Clubs will be loaded here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manufacturers Management -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#manufacturersCollapse">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-building me-2"></i><?= lang('manage_manufacturers') ?>
                                    </h5>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                                <div id="manufacturersCollapse" class="collapse">
                                    <div class="card-body">
                                        <form id="manufacturer_form" class="mb-4">
                                            <input type="hidden" name="manufacturer_id" id="manufacturer_id" value="">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label for="manufacturer_name" class="form-label"><?= lang('manufacturer_name') ?></label>
                                                    <input type="text" id="manufacturer_name" name="manufacturer_name" 
                                                           class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-success" id="add_manufacturer_btn">
                                                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_manufacturer') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancel_manufacturer_btn" style="display: none;">
                                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="manufacturers_table">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('manufacturer_name') ?></th>
                                                        <th width="150"><?= lang('actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="manufacturers_list">
                                                    <!-- Manufacturers will be loaded here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Categories Management -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#categoriesCollapse">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-tags me-2"></i><?= lang('manage_categories') ?>
                                    </h5>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                                <div id="categoriesCollapse" class="collapse">
                                    <div class="card-body">
                                        <form id="category_form" class="mb-4">
                                            <input type="hidden" name="category_id" id="category_id" value="">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label for="category_name" class="form-label"><?= lang('category_name') ?></label>
                                                    <input type="text" id="category_name" name="category_name" 
                                                        class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-success" id="add_category_btn">
                                                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_category') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancel_category_btn" style="display: none;">
                                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="categories_table">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('category_name') ?></th>
                                                        <th width="150"><?= lang('actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="categories_list">
                                                    <!-- Categories will be loaded here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Suppliers Management -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#suppliersCollapse">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-truck me-2"></i><?= lang('manage_suppliers') ?>
                                    </h5>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                                <div id="suppliersCollapse" class="collapse">
                                    <div class="card-body">
                                        <form id="supplier_form" class="mb-4">
                                            <input type="hidden" name="supplier_id" id="supplier_id" value="">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label for="supplier_name" class="form-label"><?= lang('supplier_name') ?></label>
                                                    <input type="text" id="supplier_name" name="supplier_name" 
                                                        class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" class="btn btn-success" id="add_supplier_btn">
                                                        <i class="bi bi-plus-circle me-2"></i><?= lang('add_supplier') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancel_supplier_btn" style="display: none;">
                                                        <i class="bi bi-x-circle me-2"></i><?= lang('cancel') ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="suppliers_table">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('supplier_name') ?></th>
                                                        <th width="150"><?= lang('actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="suppliers_list">
                                                    <!-- Suppliers will be loaded here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Restore & Backup Section -->
                        <div class="tab-pane fade" id="database-settings" role="tabpanel" 
                            aria-labelledby="product-settings-tab" tabindex="0">

                            <div class="mt-4">

                                <!-- Restore Section -->
                                <div class="mb-4">
                                    <h3 class="mb-3"><?= lang('restore_database_backup') ?></h3>

                                    <div class="mb-3">
                                        <label for="restoreFile" class="form-label"><?= lang('restore_sql_backup') ?></label>
                                        <input type="file" class="form-control" id="restoreFile" accept=".sql">
                                    </div>

                                    <button id="restore_db_btn" class="btn btn-warning">
                                        <i class="bi bi-upload me-2"></i><?= lang('restore_db_backup') ?>
                                    </button>
                                </div>

                                <!-- Backup Section -->
                                <div class="mb-4">
                                    <h3 class="mb-3"><?= lang('backup_database') ?></h3>

                                    <a class="btn btn-primary" id="backup_db_btn" href="/backup_database">
                                        <i class="bi bi-database-fill-up me-2"></i><?= lang('backup_database') ?>
                                    </a>
                                </div>

                            </div>

                        </div>



                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const settingsForm = document.getElementById('settings_form');
    const saveBtn = document.getElementById('save_settings_btn');
    const backupBtn = document.getElementById('backup_db_btn');
    const restoreBtn = document.getElementById('restore_db_btn');
    const restoreFileInput = document.getElementById('restoreFile');
    const messageContainer = document.getElementById('ajax_message_container');
    const csrfTokenInput = document.getElementById('csrf_token');

    // Product settings elements
    const sizeForm = document.getElementById('size_form');
    const clubForm = document.getElementById('club_form');
    const manufacturerForm = document.getElementById('manufacturer_form');
    const categoryForm = document.getElementById('category_form');
    const supplierForm = document.getElementById('supplier_form');

    function displayMessage(type, text) {
        messageContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${text}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    // --- Settings form submit (existing) ---
    settingsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= lang('saving') ?>`;
        messageContainer.innerHTML = '';

        const formData = new FormData(settingsForm);

        try {
            const response = await fetch('/savesettings_ajax', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();

            if (result.status === 'success') {
                displayMessage('success', result.message);
                csrfTokenInput.value = result.csrf_token;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                displayMessage('danger', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            displayMessage('danger', 'An unexpected error occurred. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="bi bi-save me-2"></i><?= lang('save_settings') ?>`;
        }
    });

    // --- Backup database button (existing) ---
    backupBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        backupBtn.disabled = true;
        backupBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= lang('creating_backup') ?>`;
        messageContainer.innerHTML = '';

        try {
            const response = await fetch(backupBtn.getAttribute('href'), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const contentType = response.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                const result = await response.json();
                throw new Error(result.message || 'Backup failed');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;

            const disposition = response.headers.get('content-disposition');
            let filename = 'database_backup.sql';
            if (disposition) {
                const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
            }

            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Backup error:', error);
            alert('Backup failed: ' + error.message);
        } finally {
            backupBtn.disabled = false;
            backupBtn.innerHTML = `<i class="bi bi-database-fill-up me-2"></i><?= lang('backup_database') ?>`;
        }
    });

    // --- Restore database button (existing) ---
    restoreBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        if (!restoreFileInput.files.length) {
            displayMessage('warning', 'Please select an SQL file to restore.');
            return;
        }

        restoreBtn.disabled = true;
        restoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= lang('restoring_db') ?>`;
        messageContainer.innerHTML = '';

        const formData = new FormData();
        formData.append('sqlFile', restoreFileInput.files[0]);

        try {
            const response = await fetch('/restore_database', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const result = await response.json();

            if (result.status === 'success') {
                displayMessage('success', result.message);
                restoreFileInput.value = ''; // reset file input
                setTimeout(() => window.location.reload(), 1500);
            } else {
                displayMessage('danger', result.message);
            }
        } catch (error) {
            console.error('Restore error:', error);
            displayMessage('danger', 'Restore failed: ' + error.message);
        } finally {
            restoreBtn.disabled = false;
            restoreBtn.innerHTML = `<i class="bi bi-upload me-2"></i><?= lang('restore_db_backup') ?>`;
        }
    });

    // --- Product Settings Functions ---

    // Function to reset size form to "Add" mode
    function resetSizeForm() {
        document.getElementById('size_id').value = '';
        document.getElementById('size_name').value = '';
        document.getElementById('add_size_btn').innerHTML = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_size') ?>`;
        document.getElementById('cancel_size_btn').style.display = 'none';
    }

    // Function to reset club form to "Add" mode
    function resetClubForm() {
        document.getElementById('club_id').value = '';
        document.getElementById('club_name').value = '';
        document.getElementById('add_club_btn').innerHTML = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_club') ?>`;
        document.getElementById('cancel_club_btn').style.display = 'none';
    }

    // Function to reset manufacturer form to "Add" mode
    function resetManufacturerForm() {
        document.getElementById('manufacturer_id').value = '';
        document.getElementById('manufacturer_name').value = '';
        document.getElementById('add_manufacturer_btn').innerHTML = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_manufacturer') ?>`;
        document.getElementById('cancel_manufacturer_btn').style.display = 'none';
    }

    // Add these reset functions after the existing ones
    function resetCategoryForm() {
        document.getElementById('category_id').value = '';
        document.getElementById('category_name').value = '';
        document.getElementById('add_category_btn').innerHTML = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_category') ?>`;
        document.getElementById('cancel_category_btn').style.display = 'none';
    }

    function resetSupplierForm() {
        document.getElementById('supplier_id').value = '';
        document.getElementById('supplier_name').value = '';
        document.getElementById('add_supplier_btn').innerHTML = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_supplier') ?>`;
        document.getElementById('cancel_supplier_btn').style.display = 'none';
    }

    // Generic function for handling product settings forms
    // Update the defaultAddHtml logic in handleProductForm
    async function handleProductForm(form, endpoint, addButton, cancelButton, listContainer, resetFunction) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('csrf_token', csrfTokenInput.value);
            
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Determine the "Add" button's default HTML content based on the form's identity
            let defaultAddHtml;
            if (form.id === 'size_form') {
                defaultAddHtml = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_size') ?>`;
            } else if (form.id === 'club_form') {
                defaultAddHtml = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_club') ?>`;
            } else if (form.id === 'manufacturer_form') {
                defaultAddHtml = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_manufacturer') ?>`;
            } else if (form.id === 'category_form') {
                defaultAddHtml = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_category') ?>`;
            } else if (form.id === 'supplier_form') {
                defaultAddHtml = `<i class="bi bi-plus-circle me-2"></i><?= lang('add_supplier') ?>`;
            }
            
            const isUpdate = !!formData.get(form.id.replace('_form', '_id'));
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= lang('saving') ?>`;

            let success = false;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();

                if (result.status === 'success') {
                    displayMessage('success', result.message);
                    csrfTokenInput.value = result.csrf_token;
                    
                    resetFunction();
                    submitBtn.innerHTML = defaultAddHtml;
                    loadProductSettings();
                    success = true;
                } else {
                    displayMessage('danger', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                displayMessage('danger', 'An unexpected error occurred. Please try again.');
            } finally {
                submitBtn.disabled = false;
                if (!success) {
                    submitBtn.innerHTML = originalText;
                }
            }
        });

        cancelButton.addEventListener('click', () => {
            resetFunction();
        });
    }

    // Load all product settings
    async function loadProductSettings() {
        try {
            const response = await fetch('/get_product_settings_ajax', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();

            if (result.status === 'success') {
                // Update sizes list
                document.getElementById('sizes_list').innerHTML = result.sizes.map(size => `
                    <tr>
                        <td>${size.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-size" data-id="${size.id}" data-name="${size.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-size" data-id="${size.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                // Update clubs list
                document.getElementById('clubs_list').innerHTML = result.clubs.map(club => `
                    <tr>
                        <td>${club.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-club" data-id="${club.id}" data-name="${club.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-club" data-id="${club.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                // Update manufacturers list
                document.getElementById('manufacturers_list').innerHTML = result.manufacturers.map(manufacturer => `
                    <tr>
                        <td>${manufacturer.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-manufacturer" data-id="${manufacturer.id}" data-name="${manufacturer.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-manufacturer" data-id="${manufacturer.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                // Update categories list
                document.getElementById('categories_list').innerHTML = result.categories.map(category => `
                    <tr>
                        <td>${category.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-category" data-id="${category.id}" data-name="${category.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-category" data-id="${category.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                // Update suppliers list
                document.getElementById('suppliers_list').innerHTML = result.suppliers.map(supplier => `
                    <tr>
                        <td>${supplier.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-supplier" data-id="${supplier.id}" data-name="${supplier.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-supplier" data-id="${supplier.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                // Attach event listeners to edit and delete buttons
                attachProductSettingsEvents();
            }
        } catch (error) {
            console.error('Error loading product settings:', error);
            displayMessage('danger', 'Failed to load product settings.');
        }
    }

    // Attach events to edit and delete buttons
    function attachProductSettingsEvents() {
        // Size events
        document.querySelectorAll('.edit-size').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('size_id').value = id;
                document.getElementById('size_name').value = name;
                document.getElementById('add_size_btn').innerHTML = `<i class="bi bi-check-circle me-2"></i><?= lang('update_size') ?>`;
                document.getElementById('cancel_size_btn').style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.delete-size').forEach(btn => {
            btn.addEventListener('click', () => deleteProductSetting('size', btn.dataset.id));
        });

        // Club events
        document.querySelectorAll('.edit-club').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('club_id').value = id;
                document.getElementById('club_name').value = name;
                document.getElementById('add_club_btn').innerHTML = `<i class="bi bi-check-circle me-2"></i><?= lang('update_club') ?>`;
                document.getElementById('cancel_club_btn').style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.delete-club').forEach(btn => {
            btn.addEventListener('click', () => deleteProductSetting('club', btn.dataset.id));
        });

        // Manufacturer events
        document.querySelectorAll('.edit-manufacturer').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('manufacturer_id').value = id;
                document.getElementById('manufacturer_name').value = name;
                document.getElementById('add_manufacturer_btn').innerHTML = `<i class="bi bi-check-circle me-2"></i><?= lang('update_manufacturer') ?>`;
                document.getElementById('cancel_manufacturer_btn').style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.delete-manufacturer').forEach(btn => {
            btn.addEventListener('click', () => deleteProductSetting('manufacturer', btn.dataset.id));
        });

        // Category events (MISSING - ADD THIS)
        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('category_id').value = id;
                document.getElementById('category_name').value = name;
                document.getElementById('add_category_btn').innerHTML = `<i class="bi bi-check-circle me-2"></i><?= lang('update_category') ?>`;
                document.getElementById('cancel_category_btn').style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', () => deleteProductSetting('category', btn.dataset.id));
        });

        // Supplier events (MISSING - ADD THIS)
        document.querySelectorAll('.edit-supplier').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('supplier_id').value = id;
                document.getElementById('supplier_name').value = name;
                document.getElementById('add_supplier_btn').innerHTML = `<i class="bi bi-check-circle me-2"></i><?= lang('update_supplier') ?>`;
                document.getElementById('cancel_supplier_btn').style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.delete-supplier').forEach(btn => {
            btn.addEventListener('click', () => deleteProductSetting('supplier', btn.dataset.id));
        });
    }

    // Delete product setting
    async function deleteProductSetting(type, id) {
        if (!confirm('<?= lang('confirm_delete') ?>')) return;

        try {
            const response = await fetch('/delete_product_setting_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type: type,
                    id: id,
                    csrf_token: csrfTokenInput.value
                })
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();

            if (result.status === 'success') {
                displayMessage('success', result.message);
                csrfTokenInput.value = result.csrf_token;
                loadProductSettings();
            } else {
                displayMessage('danger', result.message);
            }
        } catch (error) {
            console.error('Error deleting product setting:', error);
            displayMessage('danger', 'Failed to delete item.');
        }
    }

    // Initialize product settings forms
    function initializeProductSettings() {
        // Initialize forms with specific reset functions
        handleProductForm(
            sizeForm, 
            '/manage_size_ajax', 
            document.getElementById('add_size_btn'),
            document.getElementById('cancel_size_btn'),
            document.getElementById('sizes_list'),
            resetSizeForm
        );

        handleProductForm(
            clubForm, 
            '/manage_club_ajax', 
            document.getElementById('add_club_btn'),
            document.getElementById('cancel_club_btn'),
            document.getElementById('clubs_list'),
            resetClubForm
        );

        handleProductForm(
            manufacturerForm, 
            '/manage_manufacturer_ajax', 
            document.getElementById('add_manufacturer_btn'),
            document.getElementById('cancel_manufacturer_btn'),
            document.getElementById('manufacturers_list'),
            resetManufacturerForm
        );

        handleProductForm(
            categoryForm, 
            '/manage_category_ajax', 
            document.getElementById('add_category_btn'),
            document.getElementById('cancel_category_btn'),
            document.getElementById('categories_list'),
            resetCategoryForm
        );

        handleProductForm(
            supplierForm, 
            '/manage_supplier_ajax', 
            document.getElementById('add_supplier_btn'),
            document.getElementById('cancel_supplier_btn'),
            document.getElementById('suppliers_list'),
            resetSupplierForm
        );

        // Load initial data
        loadProductSettings();
    }

    // Initialize product settings when the tab is shown
    document.getElementById('product-settings-tab').addEventListener('shown.bs.tab', () => {
        initializeProductSettings();
    });

    // If product settings tab is active on page load, initialize it
    if (document.getElementById('product-settings-tab').classList.contains('active')) {
        initializeProductSettings();
    }
});
</script>