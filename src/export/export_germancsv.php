<?php

if (ob_get_contents()) {
    ob_end_clean();
}

// Enable all error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Corrected paths
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

// DB connection
$conn = connectDB(); 

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die('Error connecting to the database. Please try again later.');
}

// Build WHERE clause with all fields (removed 'expiration_year')
$where = [];
$params = [
    'productname', 'category', 'article_no', 'manufacturer', 'size',
    'color', 'color_number', 'supplier', 'grafted',
    'expiry_date', 'last_edited_by'
];

foreach ($params as $param) {
    error_log("DEBUG: Processing param '" . $param . "'. Value is: " . var_export($_GET[$param] ?? null, true));

    if (isset($_GET[$param]) && trim($_GET[$param]) !== '') {
        $value = $conn->real_escape_string(trim($_GET[$param]));

        if ($param === 'grafted') {
            $where[] = "grafted = " . (int)$value;
        } elseif (in_array($param, ['productname', 'category', 'article_no', 'size', 'color', 'color_number', 'supplier', 'last_edited_by'], true)) {
            $where[] = "$param LIKE '%$value%'";
        } else {
            $where[] = "$param = '$value'";
        }
    }
}

// Handle club filter (now supports multiple clubs)
if (isset($_GET['club']) && !empty($_GET['club'])) {
    $clubFilter = is_array($_GET['club']) ? $_GET['club'] : [$_GET['club']];
    $clubIds = array_map('intval', $clubFilter);
    if (!empty($clubIds)) {
        $clubPlaceholders = implode(',', $clubIds);
        $where[] = "items.id IN (SELECT item_id FROM item_clubs WHERE club_id IN ($clubPlaceholders))";
    }
}

// Handle numeric filters
$numericParams = ['unit_price', 'total_price', 'quantity'];
foreach ($numericParams as $param) {
    if (isset($_GET[$param]) && trim($_GET[$param]) !== '') {
        $value = floatval($_GET[$param]);
        $where[] = "$param = $value";
    }
}

// Handle date filters
if (isset($_GET['expiry_date']) && trim($_GET['expiry_date']) !== '') {
    $value = $conn->real_escape_string(trim($_GET['expiry_date']));
    $where[] = "expiry_date = '$value'";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT items.* FROM items $whereSQL";

$result = $conn->query($sql);
if (!$result) {
    die('Error fetching data: ' . $conn->error);
}

// Helper function removed - clubs are now stored as comma-separated string in 'club' column

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Artikel');

// Header row - German translations (removed 'Ablaufjahr')
$headers = [
    'ID',
    'Produktname',
    'Kategorie',
    'Artikelnummer',
    'Hersteller',
    'Beschreibung',
    'Größe',
    'Farbe',
    'Farbnummer',
    'Menge',
    'Stückpreis',
    'Gesamtpreis',
    'Lieferant',
    'Veredelt',
    'Verein',
    'Ablaufdatum',
    'Bild',
    'Letzte Änderung',
    'Zuletzt bearbeitet von',
    'MIME-Typ'
];

// Set headers
$colIndex = 1;
foreach ($headers as $header) {
    $sheet->setCellValue([$colIndex++, 1], $header);
}

// Data rows
$rowIndex = 2;
while ($item = $result->fetch_assoc()) {
    // Format dates and boolean values
    $formattedLastChange = '';
    if (!empty($item['last_change'])) {
        try {
            $dt = new DateTime($item['last_change']);
            $formattedLastChange = $dt->format('d.m.Y H:i:s'); // German date format
        } catch (Exception $e) {
            error_log("Date error: {$e->getMessage()}");
        }
    }

    $formattedExpiryDate = '';
    if (!empty($item['expiry_date']) && $item['expiry_date'] != '0000-00-00') {
        try {
            $dt = new DateTime($item['expiry_date']);
            $formattedExpiryDate = $dt->format('d.m.Y'); // German date format
        } catch (Exception $e) {
            error_log("Date error for expiry_date: {$e->getMessage()}");
        }
    }

    $graftedText = $item['grafted'] ? 'Ja' : 'Nein'; // German Yes/No

    // Get clubs for this item (already comma-separated in database)
    $clubsText = $item['club'] ?? '';

    // All data in order (removed expiration_year)
    $data = [
        $item['id'],
        $item['productname'],
        $item['category'],
        $item['article_no'],
        $item['manufacturer'],
        $item['description'],
        $item['size'],
        $item['color'],
        $item['color_number'],
        $item['quantity'],
        $item['unit_price'],
        $item['total_price'],
        $item['supplier'],
        $graftedText,
        $clubsText,  // Multiple clubs as comma-separated
        $formattedExpiryDate,
        !empty($item['img']) ? '[Bild]' : '', // German for Image
        $formattedLastChange,
        $item['last_edited_by'],
        $item['mime_type']
    ];

    // Set all data cells
    $colIndex = 1;
    foreach ($data as $value) {
        $sheet->setCellValue([$colIndex++, $rowIndex], $value);
    }

    $rowIndex++;
}

$conn->close();

// Output CSV headers for German Excel (semicolon delimiter)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="artikel_export.csv"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: cache, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Output UTF-8 BOM for Excel recognition
echo "\xEF\xBB\xBF";

// Output separator hint for Excel
echo "sep=;\n";

// Configure CSV writer for German Excel
$writer = new Csv($spreadsheet);
$writer->setDelimiter(';');  // ✅ CHANGED: Use semicolon for German Excel
$writer->setEnclosure('"');
$writer->setLineEnding("\r\n");
$writer->setSheetIndex(0);
$writer->setUseBOM(false);  // ✅ CHANGED: We already output BOM manually

// Save output
$writer->save('php://output');

exit;