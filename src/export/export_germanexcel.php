<?php
if (ob_get_contents()) {
    ob_end_clean();
}

// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload PhpSpreadsheet and database
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// DB connection
$conn = connectDB();
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Build WHERE clause from GET filters
$where = [];
$params = [
    'productname', 'category', 'article_no', 'manufacturer', 'size', 
    'color', 'color_number', 'supplier', 'grafted', 'club', 
    'last_edited_by'  // removed 'expiration_year'
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

// Handle numeric filters (unit_price, total_price, quantity)
$numericParams = ['unit_price', 'total_price', 'quantity'];
foreach ($numericParams as $param) {
    if (isset($_GET[$param]) && trim($_GET[$param]) !== '') {
        $value = floatval($_GET[$param]);
        $where[] = "$param = $value";
    }
}

// Handle date filters (expiry_date)
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

// Helper: convert column number to letter
function columnLetter($colNum) {
    $letter = '';
    while ($colNum > 0) {
        $mod = ($colNum - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $colNum = (int)(($colNum - $mod) / 26);
    }
    return $letter;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Artikel');

// Set column widths (removed column P for expiration_year)
$columnWidths = [
    'A' => 8,   // ID
    'B' => 20,  // Produktname (Bezeichnung)
    'C' => 15,  // Kategorie
    'D' => 15,  // Artikelnummer (Artikel-Nr.)
    'E' => 15,  // Hersteller (Firma)
    'F' => 25,  // Beschreibung
    'G' => 10,  // Größe
    'H' => 12,  // Farbe
    'I' => 12,  // Farbnummer (Farb-Nr.)
    'J' => 10,  // Menge (Anzahl)
    'K' => 12,  // Einzelpreis (EK / Stk. Netto)
    'L' => 12,  // Gesamtpreis (Summe)
    'M' => 15,  // Lieferant
    'N' => 8,   // Veredelt
    'O' => 12,  // Verein
    'P' => 12,  // Ablaufdatum (moved from Q to P)
    'Q' => 15,  // Bild (moved from R to Q)
    'R' => 18,  // Letzte Änderung (moved from S to R)
    'S' => 15,  // Zuletzt bearbeitet von (moved from T to S)
    'T' => 12,  // MIME-Typ (moved from U to T)
];

foreach ($columnWidths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

// Column headers - All in German (removed Ablaufjahr)
$headers = [
    'ID', 
    'Bezeichnung',           // Produktname
    'Kategorie', 
    'Artikel-Nr.',          // Artikelnummer
    'Firma',                // Hersteller
    'Beschreibung', 
    'Größe', 
    'Farbe', 
    'Farb-Nr.',            // Farbnummer
    'Anzahl',              // Menge/Quantity
    'EK / Stk. Netto',     // Einzelpreis/Unit Price
    'Summe',               // Gesamtpreis/Total Price
    'Lieferant',  
    'Veredelt', 
    'Verein', 
    'Ablaufdatum',         // Expiry Date (removed Ablaufjahr)
    'Bild', 
    'Letzte Änderung',
    'Zuletzt bearbeitet von',
    'MIME-Typ'
];

// Define header style with borders for individual cells
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '000000']
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'color' => ['rgb' => 'E6E6FA']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    ]
];

// Set headers with individual cell styling
foreach ($headers as $i => $header) {
    $cell = columnLetter($i + 1) . '1';
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->applyFromArray($headerStyle);
}

// Set header row height
$sheet->getRowDimension(1)->setRowHeight(25);

// Fill data
$rowIndex = 2;
while ($item = $result->fetch_assoc()) {
    // Format dates and boolean values
    $formattedLastChange = '';
    if (!empty($item['last_change'])) {
        try {
            $dateTime = new DateTime($item['last_change']);
            $formattedLastChange = $dateTime->format('d.m.Y H:i:s');  // German format
        } catch (Exception $e) {
            $formattedLastChange = '';
        }
    }

    $formattedExpiryDate = '';
    if (!empty($item['expiry_date']) && $item['expiry_date'] != '0000-00-00') {
        try {
            $dateTime = new DateTime($item['expiry_date']);
            $formattedExpiryDate = $dateTime->format('d.m.Y');  // German format DD.MM.YYYY
        } catch (Exception $e) {
            $formattedExpiryDate = '';
        }
    }

    $graftedText = $item['grafted'] ? 'Ja' : 'Nein';

    // All columns in order, including empty placeholder for image (removed expiration_year)
    $columns = [
        $item['id'],
        $item['productname'],      // Bezeichnung
        $item['category'],         // Kategorie
        $item['article_no'],       // Artikel-Nr.
        $item['manufacturer'],     // Firma
        $item['description'],      // Beschreibung
        $item['size'],             // Größe
        $item['color'],            // Farbe
        $item['color_number'],     // Farb-Nr.
        $item['quantity'],         // Anzahl
        $item['unit_price'],       // EK / Stk. Netto
        $item['total_price'],      // Summe
        $item['supplier'],         // Lieferant
        $graftedText,              // Veredelt
        $item['club'],             // Verein
        $formattedExpiryDate,      // Ablaufdatum (removed expiration_year)
        '',                        // Placeholder for image
        $formattedLastChange,      // Letzte Änderung
        $item['last_edited_by'],   // Zuletzt bearbeitet von
        $item['mime_type']         // MIME-Typ
    ];

    // Set all cell values
    foreach ($columns as $colIndex => $value) {
        $sheet->setCellValue(columnLetter($colIndex + 1) . $rowIndex, $value);
    }

    // Format numeric columns (German format with 2 decimals)
    if (!empty($item['unit_price'])) {
        $sheet->getStyle(columnLetter(11) . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
    }
    if (!empty($item['total_price'])) {
        $sheet->getStyle(columnLetter(12) . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // Set row height for rows with images
    $rowHeight = 70;
    $sheet->getRowDimension($rowIndex)->setRowHeight($rowHeight);

    // Handle BLOB image (now in column Q instead of R)
    if (!empty($item['img'])) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmpFile, $item['img']);

        $drawing = new Drawing();
        $drawing->setPath($tmpFile);
        
        // Set image dimensions to fit within cell while maintaining aspect ratio
        $maxHeight = (int)(($rowHeight * 1.333) - 10);
        $maxWidth = (int)((15 * 7) - 10);
        
        // Get original image dimensions
        $imageInfo = getimagesize($tmpFile);
        if ($imageInfo !== false) {
            $origWidth = $imageInfo[0];
            $origHeight = $imageInfo[1];
            
            // Calculate scaling to fit within cell
            $scaleHeight = $maxHeight / $origHeight;
            $scaleWidth = $maxWidth / $origWidth;
            $scale = min($scaleHeight, $scaleWidth, 1);
            
            $drawing->setWidth((int)($origWidth * $scale));
            $drawing->setHeight((int)($origHeight * $scale));
        } else {
            $drawing->setHeight($maxHeight);
        }
        
        $drawing->setCoordinates('Q' . $rowIndex); // column Q = 17 (moved from R)
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);

        register_shutdown_function(function() use ($tmpFile) {
            if (file_exists($tmpFile)) unlink($tmpFile);
        });
    }

    $rowIndex++;
}

// Apply borders to all data cells (individual cell borders)
$lastColumn = columnLetter(count($headers));
$lastRow = $rowIndex - 1;
if ($lastRow > 1) {
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray($dataStyle);
}

// Apply text wrapping and alignment for description column
$sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setWrapText(true);

// Center align certain columns (removed P for expiration_year)
$centerAlignColumns = ['A', 'J', 'N', 'P', 'T']; // ID, Anzahl, Veredelt, Ablaufdatum, MIME-Typ
foreach ($centerAlignColumns as $col) {
    if ($lastRow > 1) {
        $sheet->getStyle($col . '2:' . $col . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }
}

$conn->close();

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="artikel_export.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: cache, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;