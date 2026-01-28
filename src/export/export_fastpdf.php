<?php
// 1. Check for headers
if (headers_sent($filename, $linenum)) {
    die("Headers already sent in $filename on line $linenum");
}
while (ob_get_level()) ob_end_clean();
header_remove();

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '120');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

// Log that we've reached the PDF export
error_log("PDF Export: Starting generation at " . date('Y-m-d H:i:s'));

// Ensure absolutely no output before this point
ob_start();

try {
    // Use existing connection if available, otherwise create new one
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $conn = $GLOBALS['conn'];
    } else {
        $conn = connectDB(); 
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    }
    
    // Add timeout protection
    set_time_limit(180); // 3 minutes max

    // Filters - all fields (removed expiration_year)
    $filters = [
        'productname', 'category', 'article_no', 'manufacturer', 'size', 
        'color', 'color_number', 'supplier', 'grafted', 'club', 
        'last_edited_by'
    ];
    $whereConditions = [];
    $params = [];
    $types = '';

    foreach ($filters as $filter) {
        if (isset($_GET[$filter]) && $_GET[$filter] !== '') {
            if (in_array($filter, ['productname', 'category', 'article_no', 'size', 'color', 'color_number', 'supplier', 'last_edited_by'])) {
                // Partial match for text fields
                $whereConditions[] = "`$filter` LIKE ?";
                $params[] = '%' . $_GET[$filter] . '%';
                $types .= 's';
            } else {
                // Exact match for others
                $whereConditions[] = "`$filter` = ?";
                $params[] = $_GET[$filter];
                $types .= (is_numeric($_GET[$filter]) || $filter === 'grafted') ? 'i' : 's';
            }
        }
    }

    // Handle numeric filters
    $numericFilters = ['unit_price', 'total_price', 'quantity'];
    foreach ($numericFilters as $filter) {
        if (isset($_GET[$filter]) && $_GET[$filter] !== '') {
            $whereConditions[] = "`$filter` = ?";
            $params[] = floatval($_GET[$filter]);
            $types .= 'd';
        }
    }

    // Handle date filter
    if (isset($_GET['expiry_date']) && $_GET['expiry_date'] !== '') {
        $whereConditions[] = "`expiry_date` = ?";
        $params[] = $_GET['expiry_date'];
        $types .= 's';
    }

    $sql = "SELECT * FROM items";
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(' AND ', $whereConditions);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("SQL error: " . $conn->error);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // PDF Init - Using landscape for more columns
    $pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false);
    $pdf->SetCreator('Warehouse App');
    $pdf->SetAuthor('Warehouse Management');
    $pdf->SetTitle('Warehouse Items List - Complete Export');
    $pdf->SetMargins(8, 15, 8);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Warehouse Items List - Complete Export', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(3);

    // Headers - 20 columns (removed Exp Year)
    $headers = [
        ['label' => 'ID', 'w' => 8],
        ['label' => 'Product', 'w' => 22],
        ['label' => 'Category', 'w' => 16],
        ['label' => 'Article No', 'w' => 16],
        ['label' => 'Manufacturer', 'w' => 20],
        ['label' => 'Description', 'w' => 28],
        ['label' => 'Size', 'w' => 12],
        ['label' => 'Color', 'w' => 14],
        ['label' => 'Color No', 'w' => 14],
        ['label' => 'Unit Price', 'w' => 14],
        ['label' => 'Total Price', 'w' => 14],
        ['label' => 'Supplier', 'w' => 16],
        ['label' => 'Qty', 'w' => 10],
        ['label' => 'Grafted', 'w' => 12],
        ['label' => 'Club', 'w' => 14],
        ['label' => 'Exp Date', 'w' => 14],
        ['label' => 'Last Edited By', 'w' => 16],
        ['label' => 'Last Change', 'w' => 20],
        ['label' => 'Mime Type', 'w' => 16],
        ['label' => 'Image', 'w' => 20]
    ];

    // Function to print table header
    function printTableHeader($pdf, $headers) {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(230, 230, 250);
        foreach ($headers as $h) {
            $pdf->Cell($h['w'], 8, $h['label'], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetFillColor(255, 255, 255);
    }

    // Function to process image and get dimensions
    function processImage($imageData, $maxWidth, $maxHeight) {
        if (empty($imageData)) {
            return null;
        }

        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                return null;
            }

            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return null;
            }

            $origWidth = imagesx($image);
            $origHeight = imagesy($image);
            imagedestroy($image);

            if ($origWidth === 0 || $origHeight === 0) {
                return null;
            }

            // Calculate scaling to fit within max dimensions
            $scale = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
            
            return [
                'width' => $origWidth * $scale,
                'height' => $origHeight * $scale,
                'data' => $imageData,
                'mimeType' => $mimeType
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    // Function to get file extension from mime type
    function getExtensionFromMime($mimeType) {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => null,
        };
    }

    // Print initial header
    printTableHeader($pdf, $headers);

    // Fixed row height for all rows
    $fixedRowHeight = 15; // mm - consistent height for all rows

    // Loop through results
    $rowCount = 0;
    while ($row = $result->fetch_assoc()) {
        $rowCount++;
        
        // Log progress every 10 rows (optional - remove in production)
        if ($rowCount % 10 === 0) {
            error_log("PDF: Processing row $rowCount");
        }
        
        // Check if we need a new page
        if ($pdf->GetY() + $fixedRowHeight > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
            $pdf->AddPage();
            printTableHeader($pdf, $headers);
        }

        $currentY = $pdf->GetY();
        $currentX = $pdf->GetX();

        // Format data
        $desc = strlen($row['description']) > 100 ? substr($row['description'], 0, 97) . '...' : $row['description'];
        
        // Format dates
        $formattedExpiryDate = '';
        if (!empty($row['expiry_date']) && $row['expiry_date'] != '0000-00-00') {
            try {
                $dt = new DateTime($row['expiry_date']);
                $formattedExpiryDate = $dt->format('Y-m-d');
            } catch (Exception $e) {
                $formattedExpiryDate = $row['expiry_date'];
            }
        }

        $formattedLastChange = '';
        if (!empty($row['last_change'])) {
            try {
                $dt = new DateTime($row['last_change']);
                $formattedLastChange = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $formattedLastChange = $row['last_change'];
            }
        }

        // Format prices
        $unitPrice = !empty($row['unit_price']) ? number_format($row['unit_price'], 2) : '';
        $totalPrice = !empty($row['total_price']) ? number_format($row['total_price'], 2) : '';

        // Prepare all cell data - 20 cells (removed expiration_year)
        $cellData = [
            ['text' => $row['id'], 'align' => 'C'],
            ['text' => $row['productname'], 'align' => 'L'],
            ['text' => $row['category'] ?? '', 'align' => 'L'],
            ['text' => $row['article_no'] ?? '', 'align' => 'L'],
            ['text' => $row['manufacturer'], 'align' => 'L'],
            ['text' => $desc, 'align' => 'L'],
            ['text' => $row['size'], 'align' => 'L'],
            ['text' => $row['color'], 'align' => 'L'],
            ['text' => $row['color_number'] ?? '', 'align' => 'L'],
            ['text' => $unitPrice, 'align' => 'C'],
            ['text' => $totalPrice, 'align' => 'C'],
            ['text' => $row['supplier'] ?? '', 'align' => 'L'],
            ['text' => $row['quantity'], 'align' => 'C'],
            ['text' => ($row['grafted'] ? 'Yes' : 'No'), 'align' => 'C'],
            ['text' => $row['club'], 'align' => 'L'],
            ['text' => $formattedExpiryDate, 'align' => 'C'],
            ['text' => $row['last_edited_by'] ?? '', 'align' => 'L'],
            ['text' => $formattedLastChange, 'align' => 'C'],
            ['text' => $row['mime_type'] ?? '', 'align' => 'L'],
            ['text' => '', 'align' => 'C', 'image' => $row['img']]
        ];

        // Draw all cells in the row
        foreach ($headers as $i => $header) {
            $cellWidth = $header['w'];
            $align = $cellData[$i]['align'];
            
            // Save position
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            if ($i === 19 && !empty($cellData[$i]['image'])) {
                // Image cell - draw border first
                $pdf->Cell($cellWidth, $fixedRowHeight, '', 1, 0, 'C');
                
                // Process and draw image
                $imageInfo = processImage($cellData[$i]['image'], $cellWidth - 4, $fixedRowHeight - 4);
                if ($imageInfo) {
                    $ext = getExtensionFromMime($imageInfo['mimeType']);
                    if ($ext) {
                        $tmpFile = tempnam(sys_get_temp_dir(), 'img_') . '.' . $ext;
                        file_put_contents($tmpFile, $imageInfo['data']);
                        
                        // Calculate centered position
                        $imageX = $x + ($cellWidth - $imageInfo['width']) / 2;
                        $imageY = $y + ($fixedRowHeight - $imageInfo['height']) / 2;
                        
                        $pdf->Image($tmpFile, $imageX, $imageY, $imageInfo['width'], $imageInfo['height'], $ext, '', 'N', false, 300, '', false, false, 0, false, false, false);
                        
                        unlink($tmpFile);
                    }
                }
            } else {
                // Text cell
                $text = (string)$cellData[$i]['text'];
                
                // Draw cell with text in one call
                $pdf->Cell($cellWidth, $fixedRowHeight, $text, 1, 0, $align);
            }
        }

        // Move to next row
        $pdf->Ln($fixedRowHeight);
    }

    $stmt->close();
    
    // Don't close connection if it's shared from index.php
    if (!isset($GLOBALS['conn']) || $GLOBALS['conn'] !== $conn) {
        $conn->close();
    }

    // Clean buffer and output PDF
    ob_end_clean();
    $pdf->Output('items_complete_export_' . date('Y-m-d H:i:s') . '.pdf', 'D');
    exit;

} catch (Exception $e) {
    // Clean any output
    ob_end_clean();
    
    // Log the error
    error_log("PDF Generation Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Send error response
    header('Content-Type: text/plain');
    http_response_code(500);
    die("PDF Generation Error: " . $e->getMessage() . "\n\nPlease check server logs for details.");
}