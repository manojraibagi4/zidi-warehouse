<?php
$config = require __DIR__ . '/../src/config/app.php';
// Set the session language to the default from the config if it's not already set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $config['default_language'];
}
require_once __DIR__ . '/lang.php';

// Get the current action from the URL path for highlighting the active link
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');
$currentAction = $path === '' ? 'dashboard' : $path;

?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? $config['default_language'] ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle"><?= htmlspecialchars($config['header']) ?></title>

    <link href="/public/css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/bootstrap/bootstrap-icons.min.css">
</head>

<body class="d-flex flex-column min-vh-100">

<!-- Modern Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand fw-bold" href="/dashboard">
            <i class="bi bi-box-seam me-2"></i>
            <?= htmlspecialchars($config['header']) ?>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                
                <!-- Language Selector -->
                <li class="nav-item dropdown me-3">
                    <form id="langForm" method="get" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" onchange="this.submit()">
                        <select id="langSelect" name="lang" class="form-select form-select-sm">
                            <option value="en" <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'selected' : '' ?>>EN</option>
                            <option value="de" <?= ($_SESSION['lang'] ?? 'en') === 'de' ? 'selected' : '' ?>>DE</option>
                        </select>
                    </form>
                </li>

                <?php if (isset($_SESSION['username'])): ?>
                    <!-- User Info Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 me-2"></i>
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <?php if (!empty($_SESSION['role'])): ?>
                                <span class="badge bg-primary ms-2"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-2"></i><?= lang('logout') ?></a></li>
                        </ul>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- Alert Messages -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['message']['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php
// Check if the current action is one that should have the sidebar
$publicRoutes = ['login', 'signup'];
if (!in_array($currentAction, $publicRoutes)):
?>
    <div class="container-fluid flex-grow-1">
        <div class="row h-100">

            <!-- Sidebar Navigation -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar border-end">
                <div class="position-sticky pt-3">
                    <!-- Sidebar Toggle Button - LEFT ALIGNED -->
                    <div class="d-flex justify-content-start mb-3">
                        <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm" type="button" title="Collapse sidebar">
                            <i class="bi bi-list fs-5"></i>
                        </button>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentAction == 'dashboard') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/dashboard">
                                <i class="bi bi-speedometer2 me-2"></i>
                                <span class="sidebar-text"><?= lang('dashboard') ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentAction == 'list') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/list">
                                <i class="bi bi-boxes me-2"></i>
                                <span class="sidebar-text"><?= lang('item_overview') ?></span>
                            </a>
                        </li>
                        <?php if (!empty($_SESSION['role']) && ($_SESSION['role'] === 'Administrator' || $_SESSION['role'] === 'Warehouse')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentAction == 'create') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/create">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span class="sidebar-text"><?= lang('add_item') ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        
                        
                        
                        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
                            <!-- Admin Section - Always visible even when sidebar is collapsed -->
                             <li class="nav-item">
                                <a class="nav-link <?= ($currentAction == 'import') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/import">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>
                                    <span class="sidebar-text"><?= lang('import') ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentAction == 'export') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/export">
                                    <i class="bi bi-cloud-arrow-down me-2"></i>
                                    <span class="sidebar-text"><?= lang('export') ?></span>
                                </a>
                            </li>
                            <li class="nav-item admin-icon-item mt-3">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted text-uppercase">
                                    <i class="bi bi-shield-check me-2 admin-icon"></i>
                                    <span class="sidebar-text"><?= lang('admin') ?? 'Administration' ?></span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentAction == 'users') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/users">
                                    <i class="bi bi-people me-2 admin-icon"></i>
                                    <span class="sidebar-text"><?= lang('users') ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentAction == 'settings') ? 'active bg-primary text-white' : 'text-dark' ?> rounded" href="/settings">
                                    <i class="bi bi-gear me-2 admin-icon"></i>
                                    <span class="sidebar-text"><?= lang('settings') ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main id="mainContent" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

<?php endif; ?>