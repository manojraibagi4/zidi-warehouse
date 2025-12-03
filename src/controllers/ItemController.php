<?php
// src/controllers/ItemController.php

/**
 * Handles requests related to Item management.
 */

// This line is crucial for Composer's autoloader.
// It should typically be loaded once at the entry point of your application (e.g., index.php).
// However, if it's not loaded globally, including it here will make PHPMailer classes available.
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../includes/lang.php'; // <-- Load translations

// The 'use' statements are now sufficient because Composer's autoloader handles the file inclusion
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Don't forget this if you're using SMTP

class ItemController {
    private $itemRepository;
    private $settingsRepo;
    private $userRepo;
    private $conn; // Storing the mysqli connection object
    private array $appConfig; // To hold the application configuration
    private array $lang; // To hold language strings

    // Constants for image handling
    private const DEFAULT_PLACEHOLDER_IMG = '/img/default-placeholder.png'; // Updated path
    private const UPLOAD_BASE_DIR = __DIR__ . '/../../public/img/uploaded/'; // Make sure this directory exists and is writable!

    /**
     * Constructor for ItemController.
     *
     * @param mysqli $conn The MySQLi database connection object.
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        // ItemRepository now also expects a mysqli object
        $this->itemRepository = new ItemRepository($conn);

        $this->settingsRepo = new SettingsRepository($this->conn); // ✅ store in object
        $this->userRepo = new UserRepository($this->conn);

        error_log("DEBUG: ItemController - Constructor called successfully with MySQLi connection.");

        $this->appConfig = require __DIR__ . '/../config/app.php';
        
        // Ensure the upload directory exists
        if (!is_dir(self::UPLOAD_BASE_DIR) && !mkdir(self::UPLOAD_BASE_DIR, 0777, true)) {
            error_log("CRITICAL ERROR: Failed to create image upload directory: " . self::UPLOAD_BASE_DIR);
            // In a real application, you might want to show a user-friendly error or redirect.
        }
    }

    /**
     * Displays the dashboard page or serves data via AJAX.
     */
    // Existing code is already fine. No changes needed.
    public function dashboard(): void {
        // Check if the request is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // This is an AJAX request, so fetch data and return JSON
            header('Content-Type: application/json');
            $response = ['success' => false, 'data' => null, 'error' => ''];

            try {
                $threshold = $this->appConfig['low_stock_threshold'] ?? 10;
                
                // Get total number of items
                $totalItems = 0;
                $resultTotal = $this->conn->query("SELECT COUNT(*) AS count FROM items");
                if ($resultTotal && $row = $resultTotal->fetch_assoc()) {
                    $totalItems = $row['count'];
                }

                // Get items with low stock (quantity < threshold)
                $lowStockItems = 0;
                $resultLowStock = $this->conn->query("SELECT COUNT(*) AS count FROM items WHERE quantity < $threshold");
                if ($resultLowStock && $row = $resultLowStock->fetch_assoc()) {
                    $lowStockItems = $row['count'];
                }

                $response['success'] = true;
                $response['data'] = [
                    'totalItems' => $totalItems,
                    'lowStockItems' => $lowStockItems,
                    'lowStockThreshold' => $threshold,
                ];

            } catch (mysqli_sql_exception $e) {
                error_log("Database error in dashboard (AJAX): " . $e->getMessage());
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            
            echo json_encode($response);
            exit;
        } else {
            // This is a normal page load, so just include the view
            include __DIR__ . '/../views/dashboard.php';
        }
    }

    
    /**
     * Displays a list of items, with optional filtering.
     */
    public function listItems(): void
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $savedFilters = $this->loadSavedFilters();
        $filters = [
            'productname' => $_GET['productname'] ?? '',
            'article_no' => $_GET['article_no'] ?? '',  // ✅ NEW
            'manufacturer' => $_GET['manufacturer'] ?? '',
            'color' => $_GET['color'] ?? '',
            'size' => $_GET['size'] ?? '',
            'supplier' => $_GET['supplier'] ?? '',  // ✅ NEW
            'category' => $_GET['category'] ?? '',  // ✅ NEW
            'grafted' => $_GET['grafted'] ?? '',
            'club' => $_GET['club'] ?? '',
            'savedFilters' => $savedFilters,
            'sort' => $_GET['sort'] ?? 'id',
            'order' => $_GET['order'] ?? 'DESC',
        ];

        // Pagination
        $perPage = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $perPage;

        $items = $this->itemRepository->getAll($filters, $perPage, $offset);

        $totalItems = $this->itemRepository->countAll($filters);
        $totalPages = ceil($totalItems / $perPage);

        // Get all available options from settings tables
        $allSizes = $this->settingsRepo->getSizes();
        $allClubs = $this->settingsRepo->getClubs();
        $allManufacturers = $this->settingsRepo->getManufacturers();
        $allSuppliers = $this->settingsRepo->getSuppliers();  // ✅ NEW - from settings
        $allCategories = $this->settingsRepo->getCategories();  // ✅ NEW - from settings

        $settings = $this->settingsRepo->getSettings();

        if ($isAjax) {
            include __DIR__ . '/../views/item_list.php';
            exit;
        }
        
        include __DIR__ . '/../views/item_list.php';
    }

    public function viewLessStock(): void {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $savedFilters = $this->loadSavedFilters();
        $filters = [
            'productname' => $_GET['productname'] ?? '',
            'article_no' => $_GET['article_no'] ?? '',  // ✅ NEW
            'manufacturer' => $_GET['manufacturer'] ?? '',
            'color' => $_GET['color'] ?? '',
            'size' => $_GET['size'] ?? '',
            'supplier' => $_GET['supplier'] ?? '',  // ✅ NEW
            'category' => $_GET['category'] ?? '',  // ✅ NEW
            'grafted' => $_GET['grafted'] ?? '',
            'club' => $_GET['club'] ?? '',
            'savedFilters' => $savedFilters,
            'sort' => $_GET['sort'] ?? 'id',
            'order' => $_GET['order'] ?? 'DESC',
            'lowstock' => true
        ];
        
        $perPage = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $perPage;
        
        $items = $this->itemRepository->getAll($filters, $perPage, $offset);
        
        $totalItems = $this->itemRepository->countAll($filters);
        $totalPages = ceil($totalItems / $perPage);
        
        // Get all available options from settings tables
        $allSizes = $this->settingsRepo->getSizes();
        $allClubs = $this->settingsRepo->getClubs();
        $allManufacturers = $this->settingsRepo->getManufacturers();
        $allSuppliers = $this->settingsRepo->getSuppliers();  // ✅ NEW - from settings
        $allCategories = $this->settingsRepo->getCategories();  // ✅ NEW - from settings
        
        $settings = $this->settingsRepo->getSettings();
        
        include __DIR__ . '/../views/item_list.php';
    }

    public function export(): void {
        if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
        }
        
        $savedFilters = $this->loadSavedFilters();
        
        $filters = [
            'productname' => $_GET['productname'] ?? '',
            'article_no' => $_GET['article_no'] ?? '',  // ✅ NEW
            'manufacturer' => $_GET['manufacturer'] ?? '',
            'color' => $_GET['color'] ?? '',
            'size' => $_GET['size'] ?? '',
            'supplier' => $_GET['supplier'] ?? '',  // ✅ NEW
            'category' => $_GET['category'] ?? '',  // ✅ NEW
            'grafted' => $_GET['grafted'] ?? '',
            'club' => $_GET['club'] ?? '',
            'sort' => $_GET['sort'] ?? 'id',
            'order' => $_GET['order'] ?? 'DESC',
        ];

        $perPage = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
            ? (int)$_GET['page']
            : 1;
        $offset = ($page - 1) * $perPage;

        $items = $this->itemRepository->getAll($filters, $perPage, $offset);
        $totalItems = $this->itemRepository->countAll($filters);
        $totalPages = ceil($totalItems / $perPage);

        // Get all available options from settings tables
        $allSizes = $this->settingsRepo->getSizes();
        $allClubs = $this->settingsRepo->getClubs();
        $allManufacturers = $this->settingsRepo->getManufacturers();
        $allSuppliers = $this->settingsRepo->getSuppliers();  // ✅ NEW - from settings
        $allCategories = $this->settingsRepo->getCategories();  // ✅ NEW - from settings

        $settings = $this->settingsRepo->getSettings();

        include __DIR__ . '/../views/export_list.php';
    }
    
    // src/controllers/ItemController.php

    public function exportToExcel(): void
    {
        require_once __DIR__ . '/../export/export_germanexcel.php';
    }


    public function exportToCSV(): void
    {
        require_once __DIR__ . '/../export/export_germancsv.php';
    }

    public function exportToPDF(): void
    {
        require_once __DIR__ . '/../export/export_fastpdf.php';
    }


    public function import(): void
    {
        if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            // Option 1: redirect to dashboard with message
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
        }
        // Pass status and message from the AJAX response to the view
        $status = $_GET['status'] ?? null;
        $message = $_GET['message'] ?? null;
        include __DIR__ . '/../views/import_list.php';
    }

    


    public function importExcelFile(): void
    {
        // Start output buffering to prevent any unexpected output.
        ob_start();

        $response = [
            'success' => false,
            'message' => 'An unexpected error occurred.'
        ];

        try {
            // Require the CSRF configuration file.
            require_once __DIR__ . '/../config/csrf.php';

            // 1. Check for a valid POST request.
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method.');
            }

            // 2. Verify the CSRF token.
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token.');
            }

            // 3. Validate file upload.
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file was uploaded or an upload error occurred.');
            }

            // 4. Call the refactored import handler function.
            // The import handler must return a result, not exit on its own.
            require_once __DIR__ . '/../import/import_excelgerman.php';
            $importResult = handleExcelImport($_FILES['excel_file']['tmp_name'], $_POST['table_name'] ?? 'items');

            // 5. Build the final JSON response based on the import result.
            $response['success'] = $importResult['success'];
            $response['message'] = $importResult['message'];

              // Clear any buffered output before sending the JSON.
            ob_end_clean();

            // Set the Content-Type header and echo the JSON response.
            header('Content-Type: application/json');
            echo json_encode($response);

        // IMPORTANT: Exit to ensure nothing else is sent after the response.
        exit();

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

      
    }

    // ... other controller methods

    /**
     * Displays the form for creating a new item.
     */
    public function create(): void {
        if (empty($_SESSION['role']) || $_SESSION['role'] === 'Employee') {
            // Option 1: redirect to dashboard with message
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
        }
        $item = new Item($this->conn); // Pass connection if Item class needs it
         $settingsRepo = $this->settingsRepo;
        include __DIR__ . '/../views/item_form.php';
    }

    /**
     * Displays the form for editing an existing item.
     */
    public function edit(): void
    {
        if (empty($_SESSION['role']) || $_SESSION['role'] === 'Employee') {
            // Option 1: redirect to dashboard with message
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
        }
        // Retrieve the ID from the global $id variable set by the router
        global $id;

        if (!isset($id) || $id === null) {
            $_SESSION['message'] = ['type' => 'error', 'text' => lang('error_no_id_for_editing')];
            header("Location: /list");
            exit();
        }

        $itemId = (int)$id;
        $itemData = $this->itemRepository->find($itemId);

        if (!$itemData) {
            $_SESSION['message'] = ['type' => 'error', 'text' => lang('error_item_not_found_editing')];
            header("Location: /list");
            exit();
        }

        // Create a new Item object and populate it with data from the database array
        $item = new Item();
        foreach ($itemData as $key => $value) {
            // Use property_exists to avoid setting non-existent properties
            if (property_exists($item, $key)) {
                $item->$key = $value;
            }
        }
         $settingsRepo = $this->settingsRepo;
        // Pass the populated Item object to the view
        include __DIR__ . '/../views/item_form.php';
    }

    public function settings(): void {
        if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            // Option 1: redirect to dashboard with message
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
    
            // Option 2 (stricter): show 403 Forbidden page
            // http_response_code(403);
            // echo "<h1>403 Forbidden</h1><p>You are not allowed to access this page.</p>";
            // exit;
        }
        include __DIR__ . '/../views/settings.php';
    }

    /**
     * Stores a new item submitted via the form.
     */
    public function store(): void
    {
        ob_start();
        
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $response = ['success' => false, 'message' => lang('error_add_item_failed')];

        try {
            require_once __DIR__ . '/../config/csrf.php';
            require_once __DIR__ . '/../config/validation.php';

            // 1. CSRF check
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception(lang('csrf_failed'));
            }

            // 2. Validation
            $errors = validateItem($_POST);
            if (!empty($errors)) {
                throw new Exception(implode('<br>', $errors));
            }

            // Create and populate the Item object
            $item = new Item($this->conn);
            $item->productname = trim($_POST['productname'] ?? '');
            $item->manufacturer = trim($_POST['manufacturer'] ?? '');
            $item->description = trim($_POST['description'] ?? '');
            $item->size = trim($_POST['size'] ?? '');
            $item->color = trim($_POST['color'] ?? '');
            
            // NEW FIELDS
            $item->category = !empty(trim($_POST['category'] ?? '')) ? trim($_POST['category']) : null;
            $item->article_no = !empty(trim($_POST['article_no'] ?? '')) ? trim($_POST['article_no']) : null;
            $item->color_number = !empty(trim($_POST['color_number'] ?? '')) ? trim($_POST['color_number']) : null;
            
            // ✅ FIXED: Properly handle expiry_date - must be a valid date or NULL
            $expiry_date_raw = trim($_POST['expiry_date'] ?? '');
            if (!empty($expiry_date_raw)) {
                // Validate date format (YYYY-MM-DD)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date_raw)) {
                    $item->expiry_date = $expiry_date_raw;
                } else {
                    // Try to parse other date formats
                    $timestamp = strtotime($expiry_date_raw);
                    if ($timestamp !== false) {
                        $item->expiry_date = date('Y-m-d', $timestamp);
                    } else {
                        $item->expiry_date = null;
                    }
                }
            } else {
                $item->expiry_date = null;
            }

            $item->unit_price = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
            $item->total_price = !empty($_POST['total_price']) ? (float)$_POST['total_price'] : null;
            $item->supplier = !empty(trim($_POST['supplier'] ?? '')) ? trim($_POST['supplier']) : null;

            $item->quantity = (int)($_POST['quantity'] ?? 0);
            $item->grafted = isset($_POST['grafted']) ? 1 : 0;

            // ✅ FIXED: Only set club if grafted is checked
            $item->club = ($item->grafted === 1 && !empty(trim($_POST['club'] ?? ''))) ? trim($_POST['club']) : null;

            // ✅ FIXED: Make sure expiration_year is properly set (this is a YEAR type, not DATE)
            $expiration_year_raw = trim($_POST['expiration_year'] ?? '');
            if (!empty($expiration_year_raw) && is_numeric($expiration_year_raw)) {
                $item->expiration_year = (int)$expiration_year_raw;
            } else {
                $item->expiration_year = (int)(date('Y') + 5); // default to 5 years from now
            }
           

            

            // ✅ CRITICAL FIX: Do NOT set last_change at all - let database DEFAULT handle it
            // Remove this line completely or set to NULL
            // $item->last_change = null; // Let database handle the timestamp

            $item->last_edited_by = $_SESSION['username'] ?? 'system';
            $item->last_change = date('Y-m-d H:i:s'); // Uses PHP timezone

            // ✅ FIXED: Handle image upload properly
            $item->img = null;
            $item->mime_type = 'image/png'; // Default

            if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['img']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    // Read the file contents
                    $imageData = file_get_contents($_FILES['img']['tmp_name']);
                    
                    if ($imageData !== false && !empty($imageData)) {
                        $item->img = $imageData;
                        $item->mime_type = $fileType;
                        error_log("DEBUG: Image uploaded successfully. Size: " . strlen($imageData) . " bytes, Type: " . $fileType);
                    } else {
                        error_log("ERROR: Failed to read image file contents");
                    }
                } else {
                    error_log("ERROR: Invalid image type: " . $fileType);
                    throw new Exception("Invalid image type. Allowed types: JPEG, PNG, GIF, WebP");
                }
            } else if (isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("ERROR: Image upload error code: " . $_FILES['img']['error']);
            }

            // ✅ Add this debug line before calling create()
            error_log("DEBUG store(): About to create item. Image size: " . (($item->img !== null) ? strlen($item->img) : 'NULL') . " bytes");

            // Debug logging to see what values are being set
            error_log("DEBUG: expiry_date = " . ($item->expiry_date ?? 'NULL'));
            error_log("DEBUG: expiration_year = " . $item->expiration_year);

            // 3. Create the item in the database
            if ($this->itemRepository->create($item)) {
                $response['success'] = true;
                $response['message'] = lang('success_item_added');
                $_SESSION['message'] = ['type' => 'success', 'text' => lang('success_item_added')];

                $threshold = $this->appConfig['low_stock_threshold'] ?? 10;
                if ($item->quantity < $threshold) {
                    $this->checkLowStockAndNotify();
                }
            } else {
                throw new Exception(lang('error_add_item_failed'));
            }

        } catch (Exception $e) {
            error_log("ERROR in store(): " . $e->getMessage());
            $response['message'] = $e->getMessage();
            $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
        }

        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            ob_end_clean();
            header("Location: /list");
            exit();
        }
    }

    
    /**
     * Updates an existing item submitted via the form.
     */
    public function update(): void
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $response = ['success' => false, 'message' => lang('error_update_item_failed')];

        try {
            if (!isset($_POST['id'])) {
                throw new Exception(lang('error_no_id_for_update'));
            }
        
            // 1. CSRF check
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception(lang('csrf_failed'));
            }
        
            $id = (int)$_POST['id'];
            $itemData = $this->itemRepository->find($id);
        
            if (!$itemData) {
                throw new Exception(lang('error_item_not_found_update'));
            }
        
            // Create an Item object and populate it with existing data
            $item = new Item($this->conn);
            foreach ($itemData as $key => $value) {
                if (property_exists($item, $key)) {
                    $item->$key = $value;
                }
            }
        
            // Update properties from POST data
            $item->productname = trim($_POST['productname'] ?? $item->productname);
            $item->manufacturer = trim($_POST['manufacturer'] ?? $item->manufacturer);
            $item->description = trim($_POST['description'] ?? $item->description);
            $item->size = trim($_POST['size'] ?? $item->size);
            $item->color = trim($_POST['color'] ?? $item->color);
            
            // NEW FIELDS
            $item->category = trim($_POST['category'] ?? '') ?: null;
            $item->article_no = trim($_POST['article_no'] ?? '') ?: null;
            $item->color_number = trim($_POST['color_number'] ?? '') ?: null;
            $item->expiry_date = trim($_POST['expiry_date'] ?? '') ?: null;
            $item->unit_price = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
            $item->total_price = !empty($_POST['total_price']) ? (float)$_POST['total_price'] : null;
            $item->supplier = trim($_POST['supplier'] ?? '') ?: null;
            
            $item->quantity = (int)($_POST['quantity'] ?? $item->quantity);
            $item->grafted = isset($_POST['grafted']) ? 1 : 0;
            
            // ✅ FIXED: Only set club if grafted is checked
            $item->club = ($item->grafted === 1 && !empty(trim($_POST['club'] ?? ''))) ? trim($_POST['club']) : null;
            
            $item->expiration_year = (int)($_POST['expiration_year'] ?? $item->expiration_year);
            $item->last_edited_by = $_SESSION['username'] ?? 'system';
        
            
            $item->last_change = date('Y-m-d H:i:s');
        
            // ✅ FIXED: Handle image upload - only update if new image is uploaded
            if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['img']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    $imageData = file_get_contents($_FILES['img']['tmp_name']);
                    
                    if ($imageData !== false && !empty($imageData)) {
                        $item->img = $imageData;
                        $item->mime_type = $fileType;
                        error_log("DEBUG: New image uploaded for update. Size: " . strlen($imageData) . " bytes, Type: " . $fileType);
                    } else {
                        error_log("ERROR: Failed to read new image file contents during update");
                    }
                } else {
                    error_log("ERROR: Invalid image type during update: " . $fileType);
                    throw new Exception("Invalid image type. Allowed types: JPEG, PNG, GIF, WebP");
                }
            } else if (isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("ERROR: Image upload error during update. Code: " . $_FILES['img']['error']);
            }
            // If no new image is uploaded, keep the existing image data (already loaded from $itemData)
        
            if ($this->itemRepository->update($item)) {
                $response['success'] = true;
                $response['message'] = lang('success_item_updated');
                $_SESSION['message'] = ['type' => 'success', 'text' => lang('success_item_updated')];
        
                $threshold = $this->appConfig['low_stock_threshold'] ?? 10;
                if ($item->quantity < $threshold) {
                    $this->checkLowStockAndNotify();
                }
        
            } else {
                throw new Exception(lang('error_update_item_failed'));
            }

        } catch (Exception $e) {
            error_log("ERROR in update(): " . $e->getMessage());
            $response['message'] = $e->getMessage();
        }
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            $_SESSION['message'] = ['type' => $response['success'] ? 'success' : 'error', 'text' => $response['message']];
            header("Location: /list");
            exit();
        }
    }

    /**
     * Deletes an item from the database, handling both regular and AJAX requests.
     */
    public function delete(): void
    {
        if (empty($_SESSION['role']) || $_SESSION['role'] === 'Employee') {
            // Option 1: redirect to dashboard with message
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => lang('access_denied')
            ];
            header('Location: /dashboard');
            exit;
        }
        // Retrieve the ID from the global $id variable set by the router.
        global $id;

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $response = ['success' => false, 'message' => lang('error_generic')];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception(lang('invalid_request_method'));
            }
            
            // This check is now redundant if the router is configured correctly, but it's a good safeguard.
            if (!isset($id) || $id === null) {
                throw new Exception(lang('error_no_id_for_deletion'));
            }
            
            // The CSRF token check should be based on the form data, not the URL.
            // For AJAX requests, it should be in the POST body. For non-AJAX, it's the same.
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception(lang('invalid_csrf_token'));
            }
            
            $itemId = (int)$id; // Use the global $id variable.
            $item = $this->itemRepository->find($itemId);

            if (!$item) {
                throw new Exception(lang('error_item_not_found_deletion'));
            }

            // Only delete the image file if it's not the default placeholder.
            if ($item['img'] && $item['img'] !== self::DEFAULT_PLACEHOLDER_IMG) {
                $fullImagePath = __DIR__ . '/../../' . $item['img'];
                if (file_exists($fullImagePath) && is_file($fullImagePath)) {
                    unlink($fullImagePath);
                }
            }

            if (!$this->itemRepository->delete($itemId)) {
                throw new Exception(lang('error_delete_item_failed_db'));
            }

            $response['success'] = true;
            $response['message'] = lang('success_item_deleted');
            // This session message will be handled by the non-AJAX redirection below.
            $_SESSION['message'] = ['type' => 'success', 'text' => lang('success_item_deleted')];

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            // Set a session message for non-AJAX redirects.
            $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            // Redirection should happen after setting the session message, which is already done in the try-catch.
            header("Location: /list");
            exit();
        }
    }
    
    /**
     * Exports filtered item data to an XLSX file.
     */
    public function exportItems(): void {
        include __DIR__ . '/../views/item_form.php';
    }

    /**
     * Helper function to recursively delete a directory and its contents.
     * Used for cleaning up temporary XLSX build directories.
     *
     * @param string $dirPath The path to the directory to delete.
     */
    private function deleteDir(string $dirPath): void {
        if (!is_dir($dirPath)) {
            return;
        }
        foreach (scandir($dirPath) as $item) {
            if ($item == '.' || $item == '..') continue;
            $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;
            is_dir($itemPath) ? $this->deleteDir($itemPath) : unlink($itemPath);
        }
        rmdir($dirPath);
    }

    /**
     * Fetches items with quantity below a specified threshold from the database using mysqli.
     *
     * @param int $threshold The quantity threshold for low stock.
     * @return array An array of associative arrays, each representing a low stock item.
     */
    public function getLowStockItems(int $threshold = 10): array
    {
        $lowStockItems = [];
        $query = "SELECT * FROM items WHERE quantity < ?"; // Changed 'products' to 'items'
        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("i", $threshold);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $lowStockItems[] = $row;
            }
            $stmt->close();
        } else {
            error_log("ERROR: Failed to prepare statement for getLowStockItems: " . $this->conn->error);
        }
        return $lowStockItems;
    }
    /**
     * Checks for low stock items and triggers an email notification if found.
     * This method will use PHPMailer directly.
     */
    private function checkLowStockAndNotify()
    {
        // Use the low_stock_threshold from your app configuration
        $threshold = $this->appConfig['low_stock_threshold'] ?? 10;

        // Ensure your ItemRepository has a getLowStockItems method
        // that accepts a threshold.
        // Calling the local method within ItemController
        $lowStockItems = $this->getLowStockItems($threshold);

        if (!empty($lowStockItems)) {

            $this->sendLowStockEmail($lowStockItems);

            // Only send email if email notifications are enabled in app config
            // if ($this->appConfig['email']['enabled'] ?? false) {
            //     $this->sendLowStockEmail($lowStockItems);
            // } else {
            //     error_log("DEBUG: Email notifications are disabled in app.php. Not sending low stock alert.");
            // }
        }
    }

    /**
     * Sends a low stock email using PHPMailer.
     *
     * @param array $lowStockItems An array of associative arrays, each representing a low stock item.
     */
    private function sendLowStockEmail($lowStockItems)
    {



        // Load settings from DB
        $settings = $this->settingsRepo->getEmailSettings();
        

        // Retrieve email settings from app configuration
        $fromEmail = $settings['from_email'] ?? 'noreply@example.com';
        $fromName = $this->appConfig['email']['from_name'] ?? lang('email_from_name');
        // $toEmail = $this->appConfig['email']['to_email'] ?? 'admin@example.com';
        // $toName = $this->appConfig['email']['to_name'] ?? lang('email_to_name');

          // Load recipients from DB
        $recipients = $this->userRepo->getNotificationUsers();
        if (empty($recipients)) {
            error_log("DEBUG: No recipients found for low stock email.");
            return;
        }

        $mail = new PHPMailer(true); // Enable exceptions
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $fromEmail;
            // !!! IMPORTANT: Replace 'YOUR_APP_PASSWORD' with a Gmail App Password !!!
            // DO NOT use your regular Gmail password here, especially in production.
            $mail->Password   = $settings['app_password'] ?? ''; // Configure this securely
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8'; // Ensure proper character encoding

            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("ERROR: Invalid from email: " . $fromEmail);
                return;
            }

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            //$mail->addAddress($toEmail, $toName);

            // Add recipients
            foreach ($recipients as $user) {
                $mail->addAddress($user['email'], $user['username']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = lang('email_low_stock_subject');

            $body = "<h3>" . sprintf(lang('email_low_stock_heading'), ($this->appConfig['low_stock_threshold'] ?? 10)) . "</h3>";
            $body .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
            <thead style='background-color: #f2f2f2;'>
                <tr>
                    <th>" . lang('product_name') . "</th>
                    <th>" . lang('manufacturer') . "</th>
                    <th>" . lang('size') . "</th>
                    <th>" . lang('color') . "</th>
                    <th>" . lang('quantity') . "</th>
                    <th>" . lang('grafted') . "</th>
                    <th>" . lang('club') . "</th>
                    <th>" . lang('expiration_year') . "</th>
                    <th>" . lang('last_change') . "</th>
                </tr>
            </thead>
            <tbody>";

            foreach ($lowStockItems as $item) {
                $body .= "<tr>
                <td>" . htmlspecialchars($item['productname']) . "</td>
                <td>" . htmlspecialchars($item['manufacturer']) . "</td>
                <td>" . htmlspecialchars($item['size']) . "</td>
                <td>" . htmlspecialchars($item['color']) . "</td>
                <td>" . htmlspecialchars($item['quantity']) . "</td>
                <td>" . ($item['grafted'] ? lang('yes') : lang('no')) . "</td>
                <td>" . ($item['grafted'] ? htmlspecialchars($item['club'] ?? '-') : '-') . "</td>
                <td>" . htmlspecialchars($item['expiration_year']) . "</td>
                <td>" . htmlspecialchars($item['last_change']) . "</td>
              </tr>";
            }

            $body .= "</tbody></table><p>" . lang('email_low_stock_footer') . "</p>";

            $mail->Body = $body;
            $mail->send();
            error_log("DEBUG: Low stock email sent successfully to " . $toEmail);
        } catch (Exception $e) {
            error_log("ERROR: PHPMailer failed to send low stock email. Mailer Error: {$mail->ErrorInfo}. Exception: {$e->getMessage()}");
            // Optionally, you could set a session message for admin if email failed
            // $_SESSION['message'] = ['type' => 'warning', 'text' => 'Low stock email failed to send. Check logs.'];
        }
    }

    // Add these methods to your ItemController class (src/controllers/ItemController.php)

    /**
     * Search for items by article number
     */
    public function searchArticle(): void {
        header('Content-Type: application/json');
        $response = ['success' => false, 'items' => [], 'message' => ''];

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['article_no']) || empty(trim($input['article_no']))) {
                throw new Exception('Article number is required');
            }

            $articleNo = trim($input['article_no']);
            
            // Search for items with this article number
            $items = $this->itemRepository->findByArticleNumber($articleNo);
            
            if (empty($items)) {
                $response['message'] = 'No items found';
            } else {
                $response['success'] = true;
                $response['items'] = $items;
                $response['message'] = count($items) . ' item(s) found';
            }

        } catch (Exception $e) {
            error_log("ERROR in searchArticle(): " . $e->getMessage());
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Update item quantity only
     */
    public function updateQuantity(): void {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        try {
            require_once __DIR__ . '/../config/csrf.php';

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['item_id']) || !isset($input['quantity'])) {
                throw new Exception('Item ID and quantity are required');
            }

            $itemId = (int)$input['item_id'];
            $newQuantity = (int)$input['quantity'];

            if ($newQuantity < 0) {
                throw new Exception('Quantity cannot be negative');
            }

            // Find the item
            $itemData = $this->itemRepository->find($itemId);
            
            if (!$itemData) {
                throw new Exception('Item not found');
            }

            // Update only the quantity
            if ($this->itemRepository->updateQuantity($itemId, $newQuantity, $_SESSION['username'] ?? 'system')) {
                $response['success'] = true;
                $response['message'] = 'Quantity updated successfully';

                // Check if low stock notification is needed
                $threshold = $this->appConfig['low_stock_threshold'] ?? 10;
                if ($newQuantity < $threshold) {
                    $this->checkLowStockAndNotify();
                }
            } else {
                throw new Exception('Failed to update quantity');
            }

        } catch (Exception $e) {
            error_log("ERROR in updateQuantity(): " . $e->getMessage());
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        exit();
    }

    // Add these methods to the ItemController class

    // Modify the saveFilter method for AJAX response
    public function saveFilter(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $name = $_POST['name'] ?? '';
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Filter name is required']);
            return;
        }

        // Get current filters from query parameters or POST data
        $filters = $this->getFiltersFromRequest($_GET);
        
        // If we're coming from AJAX, we might have filters in POST too
        if (isset($_POST['filters']) && is_array($_POST['filters'])) {
            $filters = array_merge($filters, $_POST['filters']);
        }

        $success = $this->itemRepository->saveFilter($userId, $name, $filters);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Filter saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save filter']);
        }
    }

    // Modify the applySavedFilter method for AJAX
    public function applySavedFilter(): void
    {
        // Set JSON content type header first
        header('Content-Type: application/json');
        global $id;
        $id = (int)$id;

        if (!$id || !is_numeric($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid or missing filter ID.']);
            exit;
        }
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('Not authenticated');
            }

            $filter = $this->itemRepository->getSavedFilter($id, $userId);
            if (!$filter) {
                throw new Exception('Filter not found or access denied');
            }

            // Determine the target page based on source parameter or referrer
            $source = $_GET['source'] ?? '';
            $targetPage = 'list'; // default
            
            // Check if this is from export page
            if ($source === 'export') {
                $targetPage = 'export';
            } else {
                // Fallback: check referrer
                $referrer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referrer, '/export') !== false) {
                    $targetPage = 'export';
                }
            }

            // Build query parameters - remove action parameter for clean URLs
            $queryParams = $filter['filters'];
            $queryString = http_build_query($queryParams);
            
            // Use clean URLs without action parameter
            $redirectUrl = $targetPage === 'export' 
                ? "/export?$queryString" 
                : "/list?$queryString";

            echo json_encode([
                'success' => true, 
                'message' => 'Filter applied successfully',
                'filters' => $filter['filters'],
                'redirect' => $redirectUrl,
                'targetPage' => $targetPage // for debugging
            ]);
            
        } catch (Exception $e) {
            error_log("ERROR in applySavedFilter: " . $e->getMessage());
            
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        
        // Make sure to exit after sending JSON response
        exit;
    }

    // Modify the deleteSavedFilter method for AJAX
    public function deleteSavedFilter(): void
    {
        header('Content-Type: application/json');
        
        try {
            global $id;
            $id = (int)$id;

            if (!$id || !is_numeric($id)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid or missing filter ID.']);
                exit;
            }
            
            // Verify CSRF token
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception(lang('invalid_csrf_token'));
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('Not authenticated');
            }

            $success = $this->itemRepository->deleteSavedFilter($id, $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Filter deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete filter');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Add helper method to check for AJAX requests
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // Add this method to extract filters from request
    private function getFiltersFromRequest(array $request): array
    {
        $filterKeys = ['productname', 'article_no', 'manufacturer', 'color', 'size', 'supplier', 'category', 'grafted', 'club', 'sort', 'order'];  // ✅ UPDATED
        $filters = [];
        
        foreach ($filterKeys as $key) {
            if (isset($request[$key]) && $request[$key] !== '') {
                $filters[$key] = $request[$key];
            }
        }
        
        return $filters;
    }

    private function loadSavedFilters(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("DEBUG: No user_id in session, cannot load saved filters");
            return [];
        }
        
        error_log("DEBUG: Loading saved filters for user_id: " . $userId);
        $filters = $this->itemRepository->getSavedFilters($userId);
        error_log("DEBUG: Retrieved " . count($filters) . " saved filters");
        
        return $filters;
    }
}