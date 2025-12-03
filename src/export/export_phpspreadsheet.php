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
$sheet->setTitle('Items');

// Set column widths
$columnWidths = [
    'A' => 8,   // ID
    'B' => 20,  // Product Name
    'C' => 15,  // Category
    'D' => 15,  // Article No
    'E' => 15,  // Manufacturer
    'F' => 25,  // Description
    'G' => 10,  // Size
    'H' => 12,  // Color
    'I' => 12,  // Color Number
    'J' => 10,  // Quantity
    'K' => 12,  // Unit Price
    'L' => 12,  // Total Price
    'M' => 15,  // Supplier
    'N' => 8,   // Grafted
    'O' => 12,  // Club
    'P' => 15,  // Expiration Year
    'Q' => 12,  // Expiry Date
    'R' => 15,  // Image
    'S' => 18,  // Last Change
    'T' => 15,  // Last Edited By
    'U' => 12,  // Mime Type
];

foreach ($columnWidths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

// Column headers - all fields in order
$headers = [
    'ID', 
    'Product Name', 
    'Category', 
    'Article No', 
    'Manufacturer', 
    'Description', 
    'Size', 
    'Color', 
    'Color Number', 
    'Quantity',
    'Unit Price', 
    'Total Price', 
    'Supplier',  
    'Grafted', 
    'Club', 
    'Expiration Year', 
    'Expiry Date', 
    'Image', 
    'Last Change',
    'Last Edited By',
    'Mime Type'
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
            $formattedLastChange = $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $formattedLastChange = '';
        }
    }

    $formattedExpiryDate = '';
    if (!empty($item['expiry_date']) && $item['expiry_date'] != '0000-00-00') {
        try {
            $dateTime = new DateTime($item['expiry_date']);
            $formattedExpiryDate = $dateTime->format('Y-m-d');
        } catch (Exception $e) {
            $formattedExpiryDate = '';
        }
    }

    $graftedText = $item['grafted'] ? 'Yes' : 'No';

    // All columns in order, including empty placeholder for image
    $columns = [
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
        '', // Placeholder for image
        $formattedLastChange,
        $item['last_edited_by'],
        $item['mime_type']
    ];

    // Set all cell values
    foreach ($columns as $colIndex => $value) {
        $sheet->setCellValue(columnLetter($colIndex + 1) . $rowIndex, $value);
    }

    // Format numeric columns
    if (!empty($item['unit_price'])) {
        $sheet->getStyle(columnLetter(10) . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
    }
    if (!empty($item['total_price'])) {
        $sheet->getStyle(columnLetter(11) . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // Set row height for rows with images
    $rowHeight = 70;
    $sheet->getRowDimension($rowIndex)->setRowHeight($rowHeight);

    // Handle BLOB image (now in column R)
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
        
        $drawing->setCoordinates('R' . $rowIndex); // column R = 18
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

// Center align certain columns
$centerAlignColumns = ['A', 'M', 'N', 'P', 'Q', 'U']; // ID, Quantity, Grafted, Expiration Year, Expiry Date, Mime Type
foreach ($centerAlignColumns as $col) {
    if ($lastRow > 1) {
        $sheet->getStyle($col . '2:' . $col . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }
}

$conn->close();

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="items_export.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: cache, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;