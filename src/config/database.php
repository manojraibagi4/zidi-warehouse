<?php
require_once __DIR__ . '/app.php';

/**
 * Establishes a database connection using MySQLi.
 *
 * @return mysqli The MySQLi connection object.
 * @throws Exception If the connection fails.
 */


function connectDB(): mysqli {
    // $host = 'localhost';
    // $user = 'root';
    // $pass = 'root';
    // $db = 'modernwarehouse';

     // Require app.php to get the configuration array
     //$config = require __DIR__ . '/app.php';

    $config = $GLOBALS['config'] ?? null;
    if (!$config) {
        throw new Exception("Database config not loaded.");
    }

     $host = $config['host'];
     $user = $config['user'];
     $pass = $config['pass'];
     $db = $config['db'];

    // Establish a new MySQLi connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check for connection errors
    if ($conn->connect_error) {
        // Log the error for debugging, but avoid showing sensitive details on a live site
        error_log('Database Connection Failed: ' . $conn->connect_error);
        // Throw an exception or die with a generic message in production
        die('Database connection failed. Please try again later.');
    }

    return $conn;
}



?>