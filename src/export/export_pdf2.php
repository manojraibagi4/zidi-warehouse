<?php
// Ensure no output has been sent before this point
if (headers_sent()) {
    die('Headers already sent - check for whitespace before <?php or after ?>');
}

// Increase resources
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 120);
ini_set('display_errors', 0); // Disable on production
error_reporting(E_ALL);

// Absolute path to vendor autoload
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

try {
    // Database connection
    $conn = connectDB(); 
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Build WHERE clause based on filters
    $where = [];
    $params = [];
    
    if (!empty($_GET['productname'])) {
        $where[] = "productname = ?";
        $params[] = $_GET['productname'];
    }
    if (!empty($_GET['manufacturer'])) {
        $where[] = "manufacturer = ?";
        $params[] = $_GET['manufacturer'];
    }
    if (!empty($_GET['size'])) {
        $where[] = "size = ?";
        $params[] = $_GET['size'];
    }
    if (!empty($_GET['color'])) {
        $where[] = "color = ?";
        $params[] = $_GET['color'];
    }
    if (isset($_GET['grafted']) && $_GET['grafted'] !== '') {
        $where[] = "grafted = ?";
        $params[] = (int)$_GET['grafted'];
    }
    if (isset($_GET['club']) && $_GET['club'] !== '') {
        $where[] = "club = ?";
        $params[] = $_GET['club'];
    }

    // Prepare and execute query with parameters
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM items $whereSQL";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // All strings for simplicity
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Build HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body { font-family: Helvetica, Arial, sans-serif; }
            h2 { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th { background-color: #f2f2f2; text-align: left; }
            th, td { padding: 8px; border: 1px solid #ddd; }
            .img-cell { width: 80px; text-align: center; }
        </style>
    </head>
    <body>
        <h2>Items List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Manufacturer</th>
                    <th>Description</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Quantity</th>
                    <th>Grafted</th>
                    <th>Club</th>
                    <th>Expiration Year</th>
                    <th>Last Change</th>
                    <th class="img-cell">Image</th>
                </tr>
            </thead>
            <tbody>';

    while ($row = $result->fetch_assoc()) {
        $lastChange = $row['last_change'] ?? '';

        /*$imgHtml = 'N/A';

        if (!empty($row['img'])) {
            $imagePath = realpath(__DIR__ . '/../../public/' . $row['img']);
            if ($imagePath && file_exists($imagePath)) {
                $imgHtml = '<img src="' . $imagePath . '" width="60" height="60" />';
            }
        }*/

        $imgHtml = '';
        if (!empty($row['img'])) {
            $imagePath = realpath(__DIR__ . '/../../public/' . $row['img']);



            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $mimeType = mime_content_type($imagePath);
                $imgHtml = '<img src="data:' . $mimeType . ';base64,' . $imageData . '" width="60" height="60" />';
            } else {
                $imgHtml = 'Image not found';
            }
        }
        

        $html .= "
            <tr>
                <td>{$row['id']}</td>
                <td>{$row['productname']}</td>
                <td>{$row['manufacturer']}</td>
                <td>{$row['description']}</td>
                <td>{$row['size']}</td>
                <td>{$row['color']}</td>
                <td>{$row['quantity']}</td>
                <td>" . ($row['grafted'] ? 'Yes' : 'No') . "</td>
                <td>{$row['club']}</td>
                <td>{$row['expiration_year']}</td>
                <td>{$lastChange}</td>
                <td class=\"img-cell\">{$imgHtml}</td>
            </tr>";
    }

    $html .= '
            </tbody>
        </table>
    </body>
    </html>';

    // Close database connection
    $stmt->close();
    $conn->close();

    // Configure Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('tempDir', sys_get_temp_dir()); // Set temp directory
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    
    // Render PDF
    $dompdf->render();

    // Stream the file
    $dompdf->stream('items_export.pdf', [
        'Attachment' => true,
        'compress' => 1,
        'isRemoteEnabled' => true
    ]);
    
    exit;

} catch (Exception $e) {
    // Clean any remaining output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Simple error display
    header('Content-Type: text/plain');
    die('PDF Generation Error: ' . $e->getMessage());
}