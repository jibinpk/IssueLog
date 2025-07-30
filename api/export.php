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
            id, date_submitted, plugin_name, issue_type, concern_area,
            query_title, description, steps_reproduce, error_logs,
            wp_version, wc_version, plugin_version, assigned_agent,
            time_spent, recurring_issue, escalated_to_dev, status,
            resolution_notes
        FROM support_logs 
        ORDER BY date_submitted DESC
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
        'ID', 'Date Submitted', 'Plugin Name', 'Issue Type', 'Concern Area',
        'Query Title', 'Description', 'Steps to Reproduce', 'Error Logs',
        'WordPress Version', 'WooCommerce Version', 'Plugin Version',
        'Assigned Agent', 'Time Spent', 'Recurring Issue', 'Escalated to Dev',
        'Status', 'Resolution Notes'
    ];
    fputcsv($fp, $header);
    
    // Write data
    foreach ($logs as $log) {
        $row = [
            $log['id'],
            $log['date_submitted'],
            $log['plugin_name'],
            $log['issue_type'],
            $log['concern_area'],
            $log['query_title'],
            $log['description'],
            $log['steps_reproduce'],
            $log['error_logs'],
            $log['wp_version'],
            $log['wc_version'],
            $log['plugin_version'],
            $log['assigned_agent'],
            $log['time_spent'],
            $log['recurring_issue'],
            $log['escalated_to_dev'],
            $log['status'],
            $log['resolution_notes']
        ];
        fputcsv($fp, $row);
    }
    
    fclose($fp);
}

function exportToJSON($logs, $filepath) {
    // Data is already in the correct format with the new schema
    $json = json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filepath, $json) === false) {
        throw new Exception('Could not create export file');
    }
}
?>
