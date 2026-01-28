<?php
// src/config/app.php

// Load environment variables
require_once __DIR__ . '/environment.php';
Environment::load();

// Get database credentials from environment variables
$config = [
    'host' => Environment::get('DB_HOST', 'localhost'),
    'user' => Environment::get('DB_USER', 'root'),
    'pass' => Environment::get('DB_PASSWORD', ''),
    'db'   => Environment::get('DB_NAME', 'modernwarehouse'),
];

// Make $config available globally for database.php
$GLOBALS['config'] = $config;

// Now include database connection logic
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/SettingsRepository.php';

// Use the main $conn if already exists, otherwise create a temporary one
if (!isset($conn) || !$conn) {
    $conn = connectDB(); // will read from $GLOBALS['config']
    $closeConn = true;
} else {
    $closeConn = false;
}

$settingsRepo = new SettingsRepository($conn);
$dbSettings = $settingsRepo->getSettingsArray();

// Close only if we created a temporary connection
if (!empty($closeConn)) {
    $conn->close();
}

// Merge DB settings with base config
return array_merge($config, [
    'header'              => $dbSettings['header'] ?? 'ZIDI Warehouse System',
    'footer'              => $dbSettings['footer'] ?? 'ZIDI Warehouse System',
    'default_language'    => $dbSettings['default_lang'] ?? 'en',
    'low_stock_threshold' => isset($dbSettings['lowstock_threshold'])
                                ? (int)$dbSettings['lowstock_threshold']
                                : 10,
    'expiry_days'         => isset($dbSettings['expiry_days'])
                                ? (int)$dbSettings['expiry_days']
                                : 10,
]);
