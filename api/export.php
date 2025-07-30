<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize session and check authentication
initializeSession();
requireAuth();

$format = $_GET['format'] ?? '';
if (!in_array($format, ['csv', 'json'])) {
    errorResponse('Invalid export format');
}

$pdo = getDatabase();

try {
    // Get all logs
    $stmt = $pdo->query("
        SELECT 
            id, date_created, client_ref, plugin_name, plugin_version,
            wp_version, wc_version, issue_category, issue_summary,
            detailed_description, steps_reproduce, errors_logs,
            troubleshooting_steps, resolution, time_spent,
            escalated, status, recurring
        FROM support_logs 
        ORDER BY date_created DESC
    ");
    $logs = $stmt->fetchAll();
    
    // Ensure exports directory exists
    $exportDir = '../exports';
    ensureDirectoryExists($exportDir);
    
    $filename = generateExportFilename($format);
    $filepath = $exportDir . '/' . $filename;
    
    if ($format === 'csv') {
        exportToCSV($logs, $filepath);
    } else {
        exportToJSON($logs, $filepath);
    }
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    
    // Output file and clean up
    readfile($filepath);
    unlink($filepath);
    
} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    errorResponse('Export error: ' . $e->getMessage(), 500);
}

function exportToCSV($logs, $filepath) {
    $fp = fopen($filepath, 'w');
    
    if ($fp === false) {
        throw new Exception('Could not create export file');
    }
    
    // Write header
    $header = [
        'ID', 'Date Created', 'Client Ref', 'Plugin Name', 'Plugin Version',
        'WordPress Version', 'WooCommerce Version', 'Issue Category',
        'Issue Summary', 'Detailed Description', 'Steps to Reproduce',
        'Errors/Logs', 'Troubleshooting Steps', 'Resolution', 'Time Spent',
        'Escalated', 'Status', 'Recurring'
    ];
    fputcsv($fp, $header);
    
    // Write data
    foreach ($logs as $log) {
        $row = [
            $log['id'],
            $log['date_created'],
            $log['client_ref'],
            $log['plugin_name'],
            $log['plugin_version'],
            $log['wp_version'],
            $log['wc_version'],
            $log['issue_category'],
            $log['issue_summary'],
            $log['detailed_description'],
            $log['steps_reproduce'],
            $log['errors_logs'],
            $log['troubleshooting_steps'],
            $log['resolution'],
            $log['time_spent'],
            $log['escalated'] ? 'Yes' : 'No',
            $log['status'],
            $log['recurring'] ? 'Yes' : 'No'
        ];
        fputcsv($fp, $row);
    }
    
    fclose($fp);
}

function exportToJSON($logs, $filepath) {
    // Convert boolean values for JSON
    foreach ($logs as &$log) {
        $log['escalated'] = (bool)$log['escalated'];
        $log['recurring'] = (bool)$log['recurring'];
    }
    
    $json = json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filepath, $json) === false) {
        throw new Exception('Could not create export file');
    }
}
?>
