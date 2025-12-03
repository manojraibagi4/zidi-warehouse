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

// Build WHERE clause with all fields
$where = [];
$params = [
    'productname', 'category', 'article_no', 'manufacturer', 'size', 
    'color', 'color_number', 'supplier', 'grafted', 'club', 
    'expiration_year', 'last_edited_by'
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
$sql = "SELECT * FROM items $whereSQL";

$result = $conn->query($sql);
if (!$result) {
    die('Error fetching data: ' . $conn->error);
}

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Artikel');

// Header row - German translations
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
    'Ablaufjahr', 
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

    // All data in order
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
        $item['club'],
        $item['expiration_year'],
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

// Output CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="artikel_export.csv"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: cache, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Configure CSV writer
$writer = new Csv($spreadsheet);
$writer->setDelimiter(',');
$writer->setEnclosure('"');
$writer->setLineEnding("\r\n");
$writer->setSheetIndex(0);

// Set encoding for proper character support
$writer->setUseBOM(true);

// Save output
$writer->save('php://output');

exit;