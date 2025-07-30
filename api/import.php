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
        'Plugin Name' => 'plugin_name',
        'Issue Type' => 'issue_type',
        'Concern Area' => 'concern_area',
        'Query Title' => 'query_title',
        'Description' => 'description',
        'Steps to Reproduce' => 'steps_reproduce',
        'Error Logs' => 'error_logs',
        'WordPress Version' => 'wp_version',
        'WooCommerce Version' => 'wc_version',
        'Plugin Version' => 'plugin_version',
        'Assigned Agent' => 'assigned_agent',
        'Time Spent' => 'time_spent',
        'Recurring Issue' => 'recurring_issue',
        'Escalated to Dev' => 'escalated_to_dev',
        'Status' => 'status',
        'Resolution Notes' => 'resolution_notes'
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
                
                if ($field === 'escalated_to_dev' || $field === 'recurring_issue') {
                    $data[$field] = in_array(strtolower($value), ['yes', '1', 'true']) ? 'Yes' : 'No';
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
                $errors[] = "Line {$lineNumber}: Failed to insert entry";
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
                'plugin_name' => sanitizeInput($entry['plugin_name'] ?? ''),
                'issue_type' => sanitizeInput($entry['issue_type'] ?? ''),
                'concern_area' => sanitizeInput($entry['concern_area'] ?? ''),
                'query_title' => sanitizeInput($entry['query_title'] ?? ''),
                'description' => sanitizeInput($entry['description'] ?? ''),
                'steps_reproduce' => sanitizeInput($entry['steps_reproduce'] ?? ''),
                'error_logs' => sanitizeInput($entry['error_logs'] ?? ''),
                'wp_version' => sanitizeInput($entry['wp_version'] ?? ''),
                'wc_version' => sanitizeInput($entry['wc_version'] ?? ''),
                'plugin_version' => sanitizeInput($entry['plugin_version'] ?? ''),
                'assigned_agent' => sanitizeInput($entry['assigned_agent'] ?? ''),
                'time_spent' => intval($entry['time_spent'] ?? 0),
                'recurring_issue' => sanitizeInput($entry['recurring_issue'] ?? 'No'),
                'escalated_to_dev' => sanitizeInput($entry['escalated_to_dev'] ?? 'No'),
                'status' => sanitizeInput($entry['status'] ?? 'Open'),
                'resolution_notes' => sanitizeInput($entry['resolution_notes'] ?? '')
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
                $errors[] = "Entry {$lineNumber}: Failed to insert entry";
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
            plugin_name, issue_type, concern_area, query_title, description,
            steps_reproduce, error_logs, wp_version, wc_version, plugin_version,
            assigned_agent, time_spent, recurring_issue, escalated_to_dev,
            status, resolution_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['plugin_name'], $data['issue_type'], $data['concern_area'],
            $data['query_title'], $data['description'], $data['steps_reproduce'],
            $data['error_logs'], $data['wp_version'], $data['wc_version'],
            $data['plugin_version'], $data['assigned_agent'], $data['time_spent'],
            $data['recurring_issue'], $data['escalated_to_dev'], $data['status'],
            $data['resolution_notes']
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            return false;
        }
        throw $e;
    }
}
?>
