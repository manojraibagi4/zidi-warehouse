<?php
require_once __DIR__ . '/../../includes/lang.php';
require_once __DIR__ . '/../models/SettingsRepository.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/validation.php';
require_once __DIR__ . '/../config/app.php';

class SettingsController {
    private SettingsRepository $repo;

    public function __construct(mysqli $conn) {
        $this->repo = new SettingsRepository($conn);
        
    }

    /**
     * Show settings page.
     */
    public function settings(): void {
        $this->checkAdmin();
        $settings = $this->repo->getSettings(); // Always available
        include __DIR__ . '/../views/settings.php';
    }

    /**
     * Handle AJAX POST and save settings.
     * This method will return a JSON response.
     */
    public function savesettings_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        // Sanitize and trim inputs
        $lowstock_threshold = trim($_POST['lowstock_threshold'] ?? '');
        $header = trim($_POST['header'] ?? '');
        $footer = trim($_POST['footer'] ?? '');
        $default_lang = trim($_POST['default_lang'] ?? 'en');
        $from_email = trim($_POST['from_email'] ?? '');
        $app_password = trim($_POST['app_password'] ?? '');
        $date_format = trim($_POST['date_format'] ?? 'Y-m-d');
        $time_zone = trim($_POST['time_zone'] ?? 'UTC');
        $expiry_days = trim($_POST['expiry_days'] ?? '');

        // Validation
        $error = '';
        if ($lowstock_threshold === '' || !ctype_digit($lowstock_threshold)) {
            $error = lang("invalid_threshold") ?? "Low stock threshold must be a number.";
        } elseif ($default_lang === '') {
            $error = lang("invalid_language") ?? "Default language is required.";
        } elseif ($from_email !== '' && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $error = lang("invalid_email") ?? "Please provide a valid email address.";
        } elseif (!in_array($date_format, ['Y-m-d', 'd-m-Y', 'm/d/Y', 'd M Y'])) {
            $error = lang("invalid_date_format") ?? "Invalid date format selected.";
        } elseif (!in_array($time_zone, DateTimeZone::listIdentifiers())) {
            $error = lang("invalid_time_zone") ?? "Invalid time zone selected.";
        } elseif ($expiry_days === '' || !ctype_digit($expiry_days) || (int)$expiry_days < 1) {
            $error = lang("invalid_expiry_days") ?? "Expiry days must be a positive number.";
        }

        if (!empty($error)) {
            echo json_encode(['status' => 'error', 'message' => $error]);
            return;
        }

        try {
            // Save settings to the repository
            $this->repo->updateSetting('lowstock_threshold', $lowstock_threshold);
            $this->repo->updateSetting('header', $header);
            $this->repo->updateSetting('footer', $footer);
            $this->repo->updateSetting('default_lang', $default_lang);
            $this->repo->updateSetting('from_email', $from_email);
            $this->repo->updateSetting('date_format', $date_format);
            $this->repo->updateSetting('time_zone', $time_zone);
            $this->repo->updateSetting('expiry_days', $expiry_days);

            // Only update app password if a new value is provided
            if ($app_password !== '') {
                $this->repo->updateSetting('app_password', $app_password);
            }

            // Immediately apply the new language
            $_SESSION['lang'] = $default_lang;

            // Generate a new CSRF token for the next request
            $newCsrfToken = generateCsrfToken();

            // Return success response with a new CSRF token
            echo json_encode([
                'status' => 'success',
                'message' => lang('settings_updated') ?? 'Settings updated successfully!',
                'csrf_token' => $newCsrfToken
            ]);
        } catch (Exception $e) {
            // Return an error response if something goes wrong
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle database backup.
     * This method creates a gzipped SQL dump of the database.
     */
    public function backup_database(): void {
        $this->checkAdmin();
    
        try {
            // Database credentials
            // $db_host = 'localhost';
            // $db_user = 'root';
            // $db_pass = 'root';
            // $db_name = 'modernwarehouse';

            $config = $GLOBALS['config'] ?? null;
            if (!$config) {
                throw new Exception("Database config not loaded.");
            }

            $host = $config['host'];
            $user = $config['user'];
            $pass = $config['pass'];
            $db = $config['db'];

            // Define unique filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "{$db}_backup_{$timestamp}.sql";
    
            // Auto-detect mysqldump
            $mysqldump_path = $this->find_mysqldump_from_registry();
            if (!$mysqldump_path) {
                $mysqldump_path = trim(shell_exec('where mysqldump'));
            }
            if (!$mysqldump_path || !file_exists($mysqldump_path)) {
                throw new Exception("mysqldump not found. Check MySQL installation.");
            }
    
            // Build command
            $command = sprintf(
                '"%s" -h %s -u %s -p%s %s',
                $mysqldump_path,
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db)
            );
    
            // Capture mysqldump output
            $output = shell_exec($command);
    
            if (!$output || trim($output) === '') {
                throw new Exception("Backup failed. Please check credentials and permissions.");
            }
    
            // Clear any existing output
            while (ob_get_level()) {
                ob_end_clean();
            }
    
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($output));
            header('Cache-Control: private, no-transform, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
    
            // Output the backup data
            echo $output;
            
            // Important: Don't use exit here if called via AJAX
            // Instead return success response for AJAX calls
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                
                // For AJAX calls, we can't directly download
                // Need to handle differently
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Backup completed successfully',
                    'filename' => $filename,
                    'data' => base64_encode($output)
                ]);
            }
            
            exit;
    
        } catch (Exception $e) {
            // Clear any headers that might have been sent
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred during backup: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // src/controllers/SettingsController.php

    public function restore_database(): void {
        $this->checkAdmin(); // Only admins can restore

        try {
            if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No file uploaded or upload error.");
            }

            $tmpFile = $_FILES['sqlFile']['tmp_name'];
            $fileName = $_FILES['sqlFile']['name'];

            // Optional: validate file extension
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'sql') {
                throw new Exception("Only .sql files are allowed.");
            }

            // Database credentials
            // $db_host = 'localhost';
            // $db_user = 'root';
            // $db_pass = 'root';
            // $db_name = 'modernwarehouse';

            $config = $GLOBALS['config'] ?? null;
            if (!$config) {
                throw new Exception("Database config not loaded.");
            }

            $host = $config['host'];
            $user = $config['user'];
            $pass = $config['pass'];
            $db = $config['db'];

            // Auto-detect mysqldump path (or mysql.exe for import)
            $mysql_path = $this->find_mysql_from_registry();
            if (!$mysql_path) {
                $mysql_path = trim(shell_exec('where mysql'));
            }

            if (!$mysql_path || !file_exists($mysql_path)) {
                throw new Exception("mysql.exe not found. Check your installation.");
            }

            // Build command to import
            $command = sprintf(
                '"%s" -h %s -u %s -p%s %s < "%s"',
                $mysql_path,
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db),
                $tmpFile
            );

            // Execute command
            shell_exec($command);

            echo json_encode([
                'status' => 'success',
                'message' => "Database restored successfully from {$fileName}"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    
    
    private function find_mysql_from_registry(): ?string {
        $keys = [
            'HKLM\SOFTWARE\MySQL AB\MySQL Server 8.0',
            'HKLM\SOFTWARE\WOW6432Node\MySQL AB\MySQL Server 8.0',
        ];
    
        foreach ($keys as $key) {
            $output = shell_exec("reg query \"$key\" /v Location");
            if ($output) {
                if (preg_match('/Location\s+REG_SZ\s+([^\r\n]+)/', $output, $matches)) {
                    $installPath = trim($matches[1]);
                    $mysql = $installPath . '\\bin\\mysql.exe';
                    if (file_exists($mysql)) {
                        return $mysql;
                    }
                }
            }
        }
        return null;
    }
    

    public function find_mysqldump_from_registry(): ?string {
        $keys = [
            'HKLM\SOFTWARE\MySQL AB\MySQL Server 8.0',
            'HKLM\SOFTWARE\WOW6432Node\MySQL AB\MySQL Server 8.0',
        ];
    
        foreach ($keys as $key) {
            $output = shell_exec("reg query \"$key\" /v Location");
            if ($output) {
                if (preg_match('/Location\s+REG_SZ\s+([^\r\n]+)/', $output, $matches)) {
                    $installPath = trim($matches[1]);
                    $mysqldump = $installPath . '\\bin\\mysqldump.exe';
                    if (file_exists($mysqldump)) {
                        return $mysqldump;
                    }
                }
            }
        }
    
        return null; // not found
    }
    
    

    /**
     * This method can be removed or left as a fallback.
     * In this case, it will simply redirect to the settings page.
     */
    public function savesettings(): void {
        $this->settings();
    }


     /**
     * Handle AJAX request to get all product settings
     */
    public function get_product_settings_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        try {
            $settings = $this->repo->getAllProductSettings();
            
            echo json_encode([
                'status' => 'success',
                'sizes' => $settings['sizes'],
                'clubs' => $settings['clubs'],
                'manufacturers' => $settings['manufacturers'],
                'categories' => $settings['categories'] ?? [],
                'suppliers' => $settings['suppliers'] ?? []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to load product settings.']);
        }
    }

    /**
     * Handle AJAX request to manage sizes (add/edit)
     */
    public function manage_size_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $size_id = trim($_POST['size_id'] ?? '');
        $size_name = trim($_POST['size_name'] ?? '');

        // Validation
        if (empty($size_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Size name is required.']);
            return;
        }

        try {
            if (empty($size_id)) {
                // Check for duplicate before adding
                if ($this->repo->sizeExists($size_name)) {
                    echo json_encode(['status' => 'error', 'message' => 'This size already exists. Please use a different name.']);
                    return;
                }
                // Add new size
                $success = $this->repo->addSize($size_name);
                $message = $success ? 'Size added successfully!' : 'Failed to add size.';
            } else {
                // Check for duplicate before updating (excluding current ID)
                if ($this->repo->sizeExists($size_name, (int)$size_id)) {
                    echo json_encode(['status' => 'error', 'message' => 'This size already exists. Please use a different name.']);
                    return;
                }
                // Update existing size
                $success = $this->repo->updateSize((int)$size_id, $size_name);
                $message = $success ? 'Size updated successfully!' : 'Failed to update size.';
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle AJAX request to manage clubs (add/edit)
     */
    public function manage_club_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $club_id = trim($_POST['club_id'] ?? '');
        $club_name = trim($_POST['club_name'] ?? '');

        // Validation
        if (empty($club_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Club name is required.']);
            return;
        }

        try {
            if (empty($club_id)) {
                // Check for duplicate before adding
                if ($this->repo->clubExists($club_name)) {
                    echo json_encode(['status' => 'error', 'message' => 'This club already exists. Please use a different name.']);
                    return;
                }
                // Add new club
                $success = $this->repo->addClub($club_name);
                $message = $success ? 'Club added successfully!' : 'Failed to add club.';
            } else {
                // Check for duplicate before updating (excluding current ID)
                if ($this->repo->clubExists($club_name, (int)$club_id)) {
                    echo json_encode(['status' => 'error', 'message' => 'This club already exists. Please use a different name.']);
                    return;
                }
                // Update existing club
                $success = $this->repo->updateClub((int)$club_id, $club_name);
                $message = $success ? 'Club updated successfully!' : 'Failed to update club.';
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle AJAX request to manage manufacturers (add/edit)
     */
    public function manage_manufacturer_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $manufacturer_id = trim($_POST['manufacturer_id'] ?? '');
        $manufacturer_name = trim($_POST['manufacturer_name'] ?? '');

        // Validation
        if (empty($manufacturer_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Manufacturer name is required.']);
            return;
        }

        try {
            if (empty($manufacturer_id)) {
                // Check for duplicate before adding
                if ($this->repo->manufacturerExists($manufacturer_name)) {
                    echo json_encode(['status' => 'error', 'message' => 'This manufacturer already exists. Please use a different name.']);
                    return;
                }
                // Add new manufacturer
                $success = $this->repo->addManufacturer($manufacturer_name);
                $message = $success ? 'Manufacturer added successfully!' : 'Failed to add manufacturer.';
            } else {
                // Check for duplicate before updating (excluding current ID)
                if ($this->repo->manufacturerExists($manufacturer_name, (int)$manufacturer_id)) {
                    echo json_encode(['status' => 'error', 'message' => 'This manufacturer already exists. Please use a different name.']);
                    return;
                }
                // Update existing manufacturer
                $success = $this->repo->updateManufacturer((int)$manufacturer_id, $manufacturer_name);
                $message = $success ? 'Manufacturer updated successfully!' : 'Failed to update manufacturer.';
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle AJAX request to manage categories (add/edit)
     */
    public function manage_category_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : 0;
        $category_name = trim($_POST['category_name'] ?? '');

        // Validation
        if (empty($category_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
            return;
        }

        try {
            if ($category_id === 0) {
                // Check for duplicate before adding
                if ($this->repo->categoryExists($category_name)) {
                    echo json_encode(['status' => 'error', 'message' => 'This category already exists. Please use a different name.']);
                    return;
                }
                // Add new category
                $success = $this->repo->addCategory($category_name);
                $message = $success ? 'Category added successfully!' : 'Failed to add category.';
            } else {
                // Check for duplicate before updating (excluding current ID)
                if ($this->repo->categoryExists($category_name, $category_id)) {
                    echo json_encode(['status' => 'error', 'message' => 'This category already exists. Please use a different name.']);
                    return;
                }
                // Update existing category
                $success = $this->repo->updateCategory($category_id, $category_name);
                $message = $success ? 'Category updated successfully!' : 'Failed to update category.';
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle AJAX request to manage suppliers (add/edit)
     */
    public function manage_supplier_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $supplier_id = isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : 0;
        $supplier_name = trim($_POST['supplier_name'] ?? '');

        // Validation
        if (empty($supplier_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier name is required.']);
            return;
        }

        try {
            if ($supplier_id === 0) {
                // Check for duplicate before adding
                if ($this->repo->supplierExists($supplier_name)) {
                    echo json_encode(['status' => 'error', 'message' => 'This supplier already exists. Please use a different name.']);
                    return;
                }
                // Add new supplier
                $success = $this->repo->addSupplier($supplier_name);
                $message = $success ? 'Supplier added successfully!' : 'Failed to add supplier.';
            } else {
                // Check for duplicate before updating (excluding current ID)
                if ($this->repo->supplierExists($supplier_name, $supplier_id)) {
                    echo json_encode(['status' => 'error', 'message' => 'This supplier already exists. Please use a different name.']);
                    return;
                }
                // Update existing supplier
                $success = $this->repo->updateSupplier($supplier_id, $supplier_name);
                $message = $success ? 'Supplier updated successfully!' : 'Failed to update supplier.';
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Handle AJAX request to delete product settings
     */
    public function delete_product_setting_ajax(): void {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => lang("csrf_failed") ?? "Security validation failed."]);
            return;
        }

        $type = $input['type'] ?? '';
        $id = $input['id'] ?? 0;

        // Validation - Updated to include category and supplier
        if (!in_array($type, ['size', 'club', 'manufacturer', 'category', 'supplier']) || empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
            return;
        }

        try {
            $success = false;
            $message = '';

            switch ($type) {
                case 'size':
                    $success = $this->repo->deleteSize((int)$id);
                    $message = $success ? 'Size deleted successfully!' : 'Failed to delete size.';
                    break;
                case 'club':
                    $success = $this->repo->deleteClub((int)$id);
                    $message = $success ? 'Club deleted successfully!' : 'Failed to delete club.';
                    break;
                case 'manufacturer':
                    $success = $this->repo->deleteManufacturer((int)$id);
                    $message = $success ? 'Manufacturer deleted successfully!' : 'Failed to delete manufacturer.';
                    break;
                case 'category':
                    $success = $this->repo->deleteCategory((int)$id);
                    $message = $success ? 'Category deleted successfully!' : 'Failed to delete category.';
                    break;
                case 'supplier':
                    $success = $this->repo->deleteSupplier((int)$id);
                    $message = $success ? 'Supplier deleted successfully!' : 'Failed to delete supplier.';
                    break;
            }

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'csrf_token' => generateCsrfToken()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
        }
    }
    
    /**
     * Check if user is an admin; redirect otherwise.
     */
    private function checkAdmin(): void {
        if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            $_SESSION['message'] = ['type' => 'danger', 'text' => lang('access_denied')];
            header('Location: /dashboard');
            exit();
        }
    }
}