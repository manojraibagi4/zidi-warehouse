<?php
// Warehouse Management System - Main Entry Point

ob_start(); // Start output buffering at the very top

// Load security configuration first (handles environment, errors, sessions, headers)
require_once __DIR__ . '/src/config/security.php';
SecurityConfig::init();

// Cache control headers
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Load application dependencies
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/config/timezone.php';
require_once __DIR__ . '/src/config/init_admin.php';
require_once __DIR__ . '/src/config/app.php';
require_once __DIR__ . '/src/config/cors.php';

require_once __DIR__ . '/src/models/Item.php';
require_once __DIR__ . '/src/models/ItemRepository.php';
require_once __DIR__ . '/src/models/SettingsRepository.php';
require_once __DIR__ . '/src/controllers/ItemController.php';
require_once __DIR__ . '/src/controllers/AuthController.php';
require_once __DIR__ . '/src/controllers/SettingsController.php';
require_once __DIR__ . '/src/utils/Helper.php';

// Apply CORS - simple and reliable
if (!applyCorsMiddleware()) {
    ob_end_flush();
    exit();
}

$conn = connectDB();
createAdminIfNotExists();

// Rest of your existing index.php code remains exactly the same...
if (strpos($_SERVER['REQUEST_URI'], 'index.php') !== false) {
    header("Location: /dashboard");
    exit();
}

// New logic to handle both action and ID from the path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
$path_components = explode('/', $path);

$action = $path_components[0] ?? '';
$id = $path_components[1] ?? null;

// New routing logic:
if (empty($action)) {
    if (isset($_SESSION['username'])) {
        $action = 'dashboard';
    } else {
        $action = 'login';
    }
}

$publicRoutes = ['login'];

// Authentication logic
if (!isset($_SESSION['username']) && !in_array($action, $publicRoutes)) {
    header("Location: /login");
    exit();
}

// Restrict signup to logged-in users only
if ($action === 'signup' && !isset($_SESSION['username'])) {
    header("Location: /login");
    exit();
}

$authController = new AuthController($conn);
$itemController = new ItemController($conn);
$settingsController = new SettingsController($conn);

// Check for AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Actions that do NOT render HTML (API-like actions)
$earlyActions = [
    'store'             => [$itemController, 'store'],
    'update'            => [$itemController, 'update'],
    'delete'            => [$itemController, 'delete'],
    'import_file'       => [$itemController, 'importExcelFile'],
    'logout'            => [$authController, 'logout'],
    'export_excel'      => [$itemController, 'exportToExcel'],
    'export_csv'        => [$itemController, 'exportToCSV'],
    'export_pdf'        => [$itemController, 'exportToPDF'],
    'savesettings_ajax' => [$settingsController, 'savesettings_ajax'],
    'backup_database'   => [$settingsController, 'backup_database'],
    'restore_database'  => [$settingsController, 'restore_database'],
    'manage_size_ajax' => [$settingsController, 'manage_size_ajax'],
    'manage_club_ajax' => [$settingsController, 'manage_club_ajax'],
    'manage_manufacturer_ajax' => [$settingsController, 'manage_manufacturer_ajax'],
    'manage_category_ajax' => [$settingsController, 'manage_category_ajax'],  // NEW
    'manage_supplier_ajax' => [$settingsController, 'manage_supplier_ajax'],  // NEW
    'delete_product_setting_ajax' => [$settingsController, 'delete_product_setting_ajax'],
    'get_product_settings_ajax' => [$settingsController, 'get_product_settings_ajax'],
    'save_filter'           => [$itemController, 'saveFilter'],
    'search_article'    => [$itemController, 'searchArticle'],
    'update_quantity'   => [$itemController, 'updateQuantity'],
];

if (isset($earlyActions[$action])) {
    call_user_func($earlyActions[$action]);
    $conn->close();
    ob_end_flush();
    exit();
}

// HTML-rendering actions
$htmlActions = [
    'signup'                => [$authController, 'signup'],
    'login'                 => [$authController, 'login'],
    'users'                 => [$authController, 'users'],
    'edit_user'             => [$authController, 'editUser'],
    'delete_user'           => [$authController, 'deleteUser'],
    'dashboard'             => [$itemController, 'dashboard'],
    'list'                  => [$itemController, 'listItems'],
    'viewLessStock'         => [$itemController, 'viewLessStock'],
    'create'                => [$itemController, 'create'],
    'edit'                  => [$itemController, 'edit'],
    'import'                => [$itemController, 'import'],
    'export'                => [$itemController, 'export'],
    'settings'              => [$settingsController, 'settings'],
    'apply_saved_filter'    => [$itemController, 'applySavedFilter'],
    'delete_saved_filter'   => [$itemController, 'deleteSavedFilter'],
];

// Include header/footer only for non-AJAX requests
if (!$isAjax && $action !== 'login' && $action !== 'signup') {
    include __DIR__ . '/includes/header.php';
}

if (isset($htmlActions[$action])) {
    call_user_func($htmlActions[$action]);
} else {
    if (!$isAjax) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => lang('invalid_action_redirect_dashboard')
        ];
        header("Location: /dashboard");
        exit();
    } else {
        http_response_code(404);
        echo "404 Not Found";
    }
}

if (!$isAjax && $action !== 'login' && $action !== 'signup') {
    include __DIR__ . '/includes/footer.php';
}

$conn->close();
ob_end_flush();
?>