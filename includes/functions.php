<?php
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitizeOutput($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'open':
            return 'status-open';
        case 'resolved':
            return 'status-resolved';
        case 'escalated':
            return 'status-escalated';
        default:
            return 'status-default';
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function errorResponse($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

function successResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

// Validate log entry data
function validateLogEntry($data) {
    $errors = [];
    
    if (empty($data['plugin_name'])) {
        $errors[] = 'Plugin Name is required';
    }
    
    if (empty($data['issue_type'])) {
        $errors[] = 'Issue Type is required';
    }
    
    if (!in_array($data['issue_type'], ['Technical', 'Pre-sale', 'Account/Billing'])) {
        $errors[] = 'Invalid issue type value';
    }
    
    if (empty($data['query_title'])) {
        $errors[] = 'Query Title is required';
    }
    
    if (empty($data['status'])) {
        $errors[] = 'Status is required';
    }
    
    if (!in_array($data['status'], ['Open', 'Resolved', 'Escalated', 'Closed'])) {
        $errors[] = 'Invalid status value';
    }
    
    if (isset($data['time_spent']) && !is_numeric($data['time_spent'])) {
        $errors[] = 'Time spent must be a number';
    }
    
    if (isset($data['recurring_issue']) && !in_array($data['recurring_issue'], ['Yes', 'No'])) {
        $errors[] = 'Invalid recurring issue value';
    }
    
    if (isset($data['escalated_to_dev']) && !in_array($data['escalated_to_dev'], ['Yes', 'No'])) {
        $errors[] = 'Invalid escalated to dev value';
    }
    
    return $errors;
}

// Generate unique filename for exports
function generateExportFilename($format) {
    $timestamp = date('Y-m-d_H-i-s');
    return "support_logs_export_{$timestamp}.{$format}";
}

// Create directory if it doesn't exist
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}
?>
