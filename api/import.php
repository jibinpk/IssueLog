<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize session and check authentication
initializeSession();
requireAuth();
requireCSRFToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    errorResponse('No file uploaded or upload error');
}

$file = $_FILES['import_file'];
$fileInfo = pathinfo($file['name']);
$extension = strtolower($fileInfo['extension']);

if (!in_array($extension, ['csv', 'json'])) {
    errorResponse('Invalid file format. Only CSV and JSON files are allowed.');
}

// Ensure uploads directory exists
$uploadDir = '../uploads';
ensureDirectoryExists($uploadDir);

// Move uploaded file
$uploadPath = $uploadDir . '/' . uniqid() . '.' . $extension;
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    errorResponse('Failed to save uploaded file');
}

$pdo = getDatabase();

try {
    if ($extension === 'csv') {
        $result = importFromCSV($uploadPath, $pdo);
    } else {
        $result = importFromJSON($uploadPath, $pdo);
    }
    
    // Clean up uploaded file
    unlink($uploadPath);
    
    successResponse($result, 'Import completed successfully');
    
} catch (Exception $e) {
    // Clean up uploaded file
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    errorResponse('Import error: ' . $e->getMessage(), 500);
}

function importFromCSV($filepath, $pdo) {
    $handle = fopen($filepath, 'r');
    if ($handle === false) {
        throw new Exception('Could not read CSV file');
    }
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    $lineNumber = 0;
    
    // Read header
    $header = fgetcsv($handle);
    if ($header === false) {
        throw new Exception('Could not read CSV header');
    }
    
    // Map CSV columns to database fields
    $fieldMap = [
        'Client Ref' => 'client_ref',
        'Plugin Name' => 'plugin_name',
        'Plugin Version' => 'plugin_version',
        'WordPress Version' => 'wp_version',
        'WooCommerce Version' => 'wc_version',
        'Issue Category' => 'issue_category',
        'Issue Summary' => 'issue_summary',
        'Detailed Description' => 'detailed_description',
        'Steps to Reproduce' => 'steps_reproduce',
        'Errors/Logs' => 'errors_logs',
        'Troubleshooting Steps' => 'troubleshooting_steps',
        'Resolution' => 'resolution',
        'Time Spent' => 'time_spent',
        'Escalated' => 'escalated',
        'Status' => 'status',
        'Recurring' => 'recurring'
    ];
    
    $columnIndexes = [];
    foreach ($header as $index => $column) {
        if (isset($fieldMap[$column])) {
            $columnIndexes[$fieldMap[$column]] = $index;
        }
    }
    
    // Process data rows
    while (($row = fgetcsv($handle)) !== false) {
        $lineNumber++;
        
        try {
            $data = [];
            foreach ($columnIndexes as $field => $index) {
                $value = isset($row[$index]) ? trim($row[$index]) : '';
                
                if ($field === 'escalated' || $field === 'recurring') {
                    $data[$field] = in_array(strtolower($value), ['yes', '1', 'true']) ? 1 : 0;
                } elseif ($field === 'time_spent') {
                    $data[$field] = is_numeric($value) ? intval($value) : 0;
                } else {
                    $data[$field] = sanitizeInput($value);
                }
            }
            
            // Validate required fields
            $validationErrors = validateLogEntry($data);
            if (!empty($validationErrors)) {
                $errors[] = "Line {$lineNumber}: " . implode(', ', $validationErrors);
                $skipped++;
                continue;
            }
            
            // Try to insert
            if (insertLogEntry($data, $pdo)) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Line {$lineNumber}: Duplicate client reference ID";
            }
            
        } catch (Exception $e) {
            $skipped++;
            $errors[] = "Line {$lineNumber}: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    return [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

function importFromJSON($filepath, $pdo) {
    $content = file_get_contents($filepath);
    if ($content === false) {
        throw new Exception('Could not read JSON file');
    }
    
    $data = json_decode($content, true);
    if ($data === null) {
        throw new Exception('Invalid JSON format');
    }
    
    if (!is_array($data)) {
        throw new Exception('JSON must contain an array of log entries');
    }
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($data as $index => $entry) {
        $lineNumber = $index + 1;
        
        try {
            // Sanitize and prepare data
            $logData = [
                'client_ref' => sanitizeInput($entry['client_ref'] ?? ''),
                'plugin_name' => sanitizeInput($entry['plugin_name'] ?? ''),
                'plugin_version' => sanitizeInput($entry['plugin_version'] ?? ''),
                'wp_version' => sanitizeInput($entry['wp_version'] ?? ''),
                'wc_version' => sanitizeInput($entry['wc_version'] ?? ''),
                'issue_category' => sanitizeInput($entry['issue_category'] ?? ''),
                'issue_summary' => sanitizeInput($entry['issue_summary'] ?? ''),
                'detailed_description' => sanitizeInput($entry['detailed_description'] ?? ''),
                'steps_reproduce' => sanitizeInput($entry['steps_reproduce'] ?? ''),
                'errors_logs' => sanitizeInput($entry['errors_logs'] ?? ''),
                'troubleshooting_steps' => sanitizeInput($entry['troubleshooting_steps'] ?? ''),
                'resolution' => sanitizeInput($entry['resolution'] ?? ''),
                'time_spent' => intval($entry['time_spent'] ?? 0),
                'escalated' => isset($entry['escalated']) && $entry['escalated'] ? 1 : 0,
                'status' => sanitizeInput($entry['status'] ?? 'Open'),
                'recurring' => isset($entry['recurring']) && $entry['recurring'] ? 1 : 0
            ];
            
            // Validate required fields
            $validationErrors = validateLogEntry($logData);
            if (!empty($validationErrors)) {
                $errors[] = "Entry {$lineNumber}: " . implode(', ', $validationErrors);
                $skipped++;
                continue;
            }
            
            // Try to insert
            if (insertLogEntry($logData, $pdo)) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Entry {$lineNumber}: Duplicate client reference ID";
            }
            
        } catch (Exception $e) {
            $skipped++;
            $errors[] = "Entry {$lineNumber}: " . $e->getMessage();
        }
    }
    
    return [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

function insertLogEntry($data, $pdo) {
    try {
        $sql = "INSERT INTO support_logs (
            client_ref, plugin_name, plugin_version, wp_version, wc_version,
            issue_category, issue_summary, detailed_description, steps_reproduce,
            errors_logs, troubleshooting_steps, resolution, time_spent,
            escalated, status, recurring
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['client_ref'], $data['plugin_name'], $data['plugin_version'],
            $data['wp_version'], $data['wc_version'], $data['issue_category'],
            $data['issue_summary'], $data['detailed_description'], $data['steps_reproduce'],
            $data['errors_logs'], $data['troubleshooting_steps'], $data['resolution'],
            $data['time_spent'], $data['escalated'], $data['status'], $data['recurring']
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            return false;
        }
        throw $e;
    }
}
?>
