<?php
if (ob_get_contents()) ob_end_clean();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

function sendJsonStatus($status, $message) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

function redirectWithStatus($status, $message) {
    header("Location: /import&status=" . urlencode($status) . "&message=" . urlencode($message));
    exit();
}

/**
 * Handles the import of an Excel file into a database table.
 *
 * @param string $filePath The temporary path to the uploaded file.
 * @param string $tableName The name of the target database table.
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function handleExcelImport(string $filePath, string $tableName): array
{
    $tableMappings = [
        'items' => [
            'ID'              => ['db_column' => 'id',              'type' => 'i', 'is_key' => true],
            'Product Name'    => ['db_column' => 'productname',     'type' => 's'],
            'Category'        => ['db_column' => 'category',        'type' => 's'],
            'Article No'      => ['db_column' => 'article_no',      'type' => 's'],
            'Manufacturer'    => ['db_column' => 'manufacturer',    'type' => 's'],
            'Description'     => ['db_column' => 'description',     'type' => 's'],
            'Size'            => ['db_column' => 'size',            'type' => 's'],
            'Color'           => ['db_column' => 'color',           'type' => 's'],
            'Color Number'    => ['db_column' => 'color_number',    'type' => 's'],
            'Unit Price'      => ['db_column' => 'unit_price',      'type' => 'd'],
            'Total Price'     => ['db_column' => 'total_price',     'type' => 'd'],
            'Supplier'        => ['db_column' => 'supplier',        'type' => 's'],
            'Quantity'        => ['db_column' => 'quantity',        'type' => 'i'],
            'Grafted'         => ['db_column' => 'grafted',         'type' => 'b'], 
            'Club'            => ['db_column' => 'club',            'type' => 's'],
            'Expiration Year' => ['db_column' => 'expiration_year', 'type' => 'y'],
            'Expiry Date'     => ['db_column' => 'expiry_date',     'type' => 'date'],
            'Image'           => ['db_column' => 'img',             'type' => 'blob'],
            'Last Change'     => ['db_column' => 'last_change',     'type' => 'dt'],
            'Last Edited By'  => ['db_column' => 'last_edited_by',  'type' => 's'],
            'MIME Type'       => ['db_column' => 'mime_type',       'type' => 's'],
        ],
    ];

    if (!isset($tableMappings[$tableName])) {
        return ['success' => false, 'message' => 'Invalid target table.'];
    }

    $mapping = $tableMappings[$tableName];

    // Determine primary key
    $primaryKeyHeader = null;
    $primaryKeyDbColumn = null;
    $primaryKeyType = null;
    foreach ($mapping as $header => $map) {
        if (!empty($map['is_key'])) {
            $primaryKeyHeader = $header;
            $primaryKeyDbColumn = $map['db_column'];
            $primaryKeyType = $map['type'];
            break;
        }
    }

    if (!$primaryKeyHeader) {
        return ['success' => false, 'message' => 'No primary key defined.'];
    }
    
    $conn = connectDB();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    }

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        
        if ($highestRow < 2) {
             return ['success' => false, 'message' => 'The file is empty or does not contain data.'];
        }

        // Build header map
        $excelHeaders = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $coord = Coordinate::stringFromColumnIndex($col) . '1';
            $excelHeaders[$col] = trim($sheet->getCell($coord)->getValue());
        }

        // Map images to cells with MIME type detection
        $imagesByCell = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            $coords = $drawing->getCoordinates();
            $mimeType = 'image/png'; // Default
            
            if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                ob_start();
                call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
                $imageData = ob_get_clean();
                $mimeType = $drawing->getMimeType() ?: 'image/png';
            } else {
                $path = $drawing->getPath();
                $imageData = file_get_contents($path);
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mimeType = match($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'webp' => 'image/webp',
                    default => 'image/png'
                };
            }
            $imagesByCell[$coords] = ['data' => $imageData, 'mime' => $mimeType];
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        // Loop through rows
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $primaryKeyValue = null;
            $isUpdate = false;
            $imageMimeType = 'image/png';

            for ($col = 1; $col <= $highestColIndex; $col++) {
                $header = $excelHeaders[$col] ?? null;
                if (!$header || !isset($mapping[$header])) continue;

                $dbColumn = $mapping[$header]['db_column'];
                $type = $mapping[$header]['type'];

                $coordinate = Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $sheet->getCell($coordinate);
                $cellValue = $cell->getValue();
                $processedValue = null;

                if ($dbColumn === 'img') {
                    if (isset($imagesByCell[$coordinate])) {
                        $processedValue = $imagesByCell[$coordinate]['data'];
                        $imageMimeType = $imagesByCell[$coordinate]['mime'];
                    } else {
                        $processedValue = null;
                    }
                } elseif ($type === 'i') {
                    $processedValue = is_numeric($cellValue) ? (int)$cellValue : null;
                } elseif ($type === 'd') {
                    $processedValue = is_numeric($cellValue) ? (float)$cellValue : null;
                } elseif ($type === 'b') {
                    $processedValue = (strtolower(trim($cellValue)) === 'true' || trim($cellValue) === '1') ? 1 : 0;
                } elseif ($type === 'y') {
                    // Year type - extract 4-digit year
                    $processedValue = is_numeric($cellValue) ? (int)$cellValue : (int)date('Y', strtotime($cellValue));
                } elseif ($type === 'date') {
                    // Date type
                    if (empty($cellValue)) {
                        $processedValue = null;
                    } elseif (is_numeric($cellValue)) {
                        $processedValue = date('Y-m-d', Date::excelToTimestamp($cellValue));
                    } else {
                        $timestamp = strtotime($cellValue);
                        $processedValue = $timestamp ? date('Y-m-d', $timestamp) : null;
                    }
                } elseif ($type === 'dt') {
                    // Datetime type
                    if (empty($cellValue)) {
                        $processedValue = null;
                    } elseif (is_numeric($cellValue)) {
                        $processedValue = date('Y-m-d H:i:s', Date::excelToTimestamp($cellValue));
                    } else {
                        $timestamp = strtotime($cellValue);
                        $processedValue = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                    }
                } else {
                    $processedValue = !empty($cellValue) ? (string)$cellValue : null;
                }

                $rowData[$header] = $processedValue;
                if ($header === $primaryKeyHeader) {
                    $primaryKeyValue = $processedValue;
                    $isUpdate = !empty($primaryKeyValue);
                }
            }

            // Skip completely empty rows
            if (empty(array_filter($rowData, function($val) { return $val !== null && $val !== ''; }))) continue;

            $dbColumns = [];
            $bindValues = [];
            $bindTypes = '';

            foreach ($excelHeaders as $colIndex => $header) {
                if (!isset($mapping[$header])) continue;
                $dbCol = $mapping[$header]['db_column'];
                if ($dbCol === $primaryKeyDbColumn) continue;

                $dbColumns[] = $dbCol;
                $val = $rowData[$header];
                $bindValues[] = $val;
                
                // Determine bind type
                if ($dbCol === 'img') {
                    $bindTypes .= 'b';
                } elseif ($mapping[$header]['type'] === 'blob') {
                    $bindTypes .= 'b';
                } elseif ($mapping[$header]['type'] === 'd') {
                    $bindTypes .= 'd';
                } elseif ($mapping[$header]['type'] === 'i' || $mapping[$header]['type'] === 'y') {
                    $bindTypes .= 'i';
                } else {
                    $bindTypes .= 's';
                }
            }

            // Add mime_type if image was uploaded
            if (isset($rowData['Image']) && $rowData['Image'] !== null && !in_array('mime_type', $dbColumns)) {
                $dbColumns[] = 'mime_type';
                $bindValues[] = $imageMimeType;
                $bindTypes .= 's';
            }

            // Check if update needed
            if ($isUpdate) {
                $stmtCheck = $conn->prepare("SELECT {$primaryKeyDbColumn} FROM {$tableName} WHERE {$primaryKeyDbColumn}=?");
                $stmtCheck->bind_param($primaryKeyType, $primaryKeyValue);
                $stmtCheck->execute();
                $res = $stmtCheck->get_result();
                $stmtCheck->close();
                $isUpdate = ($res->num_rows > 0);
            }

            if ($isUpdate) {
                $setSql = implode(' = ?, ', $dbColumns) . ' = ?';
                $updateSql = "UPDATE {$tableName} SET {$setSql} WHERE {$primaryKeyDbColumn}=?";
                $stmt = $conn->prepare($updateSql);

                $types = $bindTypes . $primaryKeyType;
                $params = array_merge([$types], $bindValues, [$primaryKeyValue]);
                call_user_func_array([$stmt, 'bind_param'], refValues($params));

                // Handle BLOB data
                foreach ($dbColumns as $i => $col) {
                    if (($col === 'img') && $bindValues[$i] !== null) {
                        $stmt->send_long_data($i, $bindValues[$i]);
                    }
                }

                if ($stmt->execute()) $updated++;
                else $errors[] = "Row {$row} update failed: " . $stmt->error;
                $stmt->close();
            } else {
                $placeholders = implode(', ', array_fill(0, count($dbColumns), '?'));
                $colsStr = implode(', ', $dbColumns);
                $insertSql = "INSERT INTO {$tableName} ({$colsStr}) VALUES ({$placeholders})";
                $stmt = $conn->prepare($insertSql);
                call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$bindTypes], $bindValues)));

                // Handle BLOB data
                foreach ($dbColumns as $i => $col) {
                    if (($col === 'img') && $bindValues[$i] !== null) {
                        $stmt->send_long_data($i, $bindValues[$i]);
                    }
                }

                if ($stmt->execute()) $imported++;
                else $errors[] = "Row {$row} insert failed: " . $stmt->error;
                $stmt->close();
            }
        }

        $conn->close();

        $msg = "Imported {$imported} rows, updated {$updated} rows.";
        
        if (!empty($errors)) {
            $msg .= ' ' . (lang('import_with_errors') ?? 'Errors: ') . implode('; ', $errors);
        }
        
        return ['success' => true, 'message' => $msg];

    } catch (\Exception $e) {
        error_log("Import error: " . $e->getMessage());
        return ['success' => false, 'message' => lang('unexpected_import_error') . ': ' . $e->getMessage()];
    }
}

function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) $refs[$key] = &$arr[$key];
    return $refs;
}
?>