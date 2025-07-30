<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize session and check authentication
initializeSession();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDatabase();

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        requireCSRFToken();
        handlePostRequest();
        break;
    case 'PUT':
        requireCSRFToken();
        handlePutRequest();
        break;
    case 'DELETE':
        requireCSRFToken();
        handleDeleteRequest();
        break;
    default:
        errorResponse('Method not allowed', 405);
}

function handleGetRequest() {
    global $pdo;
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $plugin = $_GET['plugin'] ?? '';
    $category = $_GET['category'] ?? '';
    $recurring = $_GET['recurring'] ?? '';
    $escalated = $_GET['escalated'] ?? '';
    
    $sql = "SELECT * FROM support_logs WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (issue_summary LIKE ? OR plugin_name LIKE ? OR errors_logs LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if (!empty($plugin)) {
        $sql .= " AND plugin_name = ?";
        $params[] = $plugin;
    }
    
    if (!empty($category)) {
        $sql .= " AND issue_category = ?";
        $params[] = $category;
    }
    
    if ($recurring === 'true') {
        $sql .= " AND recurring = 1";
    } elseif ($recurring === 'false') {
        $sql .= " AND recurring = 0";
    }
    
    if ($escalated === 'true') {
        $sql .= " AND escalated = 1";
    } elseif ($escalated === 'false') {
        $sql .= " AND escalated = 0";
    }
    
    $sql .= " ORDER BY date_created DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        successResponse($logs);
    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function handlePostRequest() {
    global $pdo;
    
    $data = [
        'client_ref' => sanitizeInput($_POST['client_ref'] ?? ''),
        'plugin_name' => sanitizeInput($_POST['plugin_name'] ?? ''),
        'plugin_version' => sanitizeInput($_POST['plugin_version'] ?? ''),
        'wp_version' => sanitizeInput($_POST['wp_version'] ?? ''),
        'wc_version' => sanitizeInput($_POST['wc_version'] ?? ''),
        'issue_category' => sanitizeInput($_POST['issue_category'] ?? ''),
        'issue_summary' => sanitizeInput($_POST['issue_summary'] ?? ''),
        'detailed_description' => sanitizeInput($_POST['detailed_description'] ?? ''),
        'steps_reproduce' => sanitizeInput($_POST['steps_reproduce'] ?? ''),
        'errors_logs' => sanitizeInput($_POST['errors_logs'] ?? ''),
        'troubleshooting_steps' => sanitizeInput($_POST['troubleshooting_steps'] ?? ''),
        'resolution' => sanitizeInput($_POST['resolution'] ?? ''),
        'time_spent' => intval($_POST['time_spent'] ?? 0),
        'escalated' => isset($_POST['escalated']) ? 1 : 0,
        'status' => sanitizeInput($_POST['status'] ?? 'Open'),
        'recurring' => isset($_POST['recurring']) ? 1 : 0
    ];
    
    // Validate data
    $errors = validateLogEntry($data);
    if (!empty($errors)) {
        errorResponse(implode(', ', $errors));
    }
    
    try {
        $sql = "INSERT INTO support_logs (
            client_ref, plugin_name, plugin_version, wp_version, wc_version,
            issue_category, issue_summary, detailed_description, steps_reproduce,
            errors_logs, troubleshooting_steps, resolution, time_spent,
            escalated, status, recurring
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['client_ref'], $data['plugin_name'], $data['plugin_version'],
            $data['wp_version'], $data['wc_version'], $data['issue_category'],
            $data['issue_summary'], $data['detailed_description'], $data['steps_reproduce'],
            $data['errors_logs'], $data['troubleshooting_steps'], $data['resolution'],
            $data['time_spent'], $data['escalated'], $data['status'], $data['recurring']
        ]);
        
        $logId = $pdo->lastInsertId();
        successResponse(['id' => $logId], 'Log entry created successfully');
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            errorResponse('A log entry with this Client Reference ID already exists');
        } else {
            errorResponse('Database error: ' . $e->getMessage(), 500);
        }
    }
}

function handlePutRequest() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $putData);
    
    $id = intval($putData['id'] ?? 0);
    if ($id <= 0) {
        errorResponse('Invalid log ID');
    }
    
    $data = [
        'client_ref' => sanitizeInput($putData['client_ref'] ?? ''),
        'plugin_name' => sanitizeInput($putData['plugin_name'] ?? ''),
        'plugin_version' => sanitizeInput($putData['plugin_version'] ?? ''),
        'wp_version' => sanitizeInput($putData['wp_version'] ?? ''),
        'wc_version' => sanitizeInput($putData['wc_version'] ?? ''),
        'issue_category' => sanitizeInput($putData['issue_category'] ?? ''),
        'issue_summary' => sanitizeInput($putData['issue_summary'] ?? ''),
        'detailed_description' => sanitizeInput($putData['detailed_description'] ?? ''),
        'steps_reproduce' => sanitizeInput($putData['steps_reproduce'] ?? ''),
        'errors_logs' => sanitizeInput($putData['errors_logs'] ?? ''),
        'troubleshooting_steps' => sanitizeInput($putData['troubleshooting_steps'] ?? ''),
        'resolution' => sanitizeInput($putData['resolution'] ?? ''),
        'time_spent' => intval($putData['time_spent'] ?? 0),
        'escalated' => isset($putData['escalated']) ? 1 : 0,
        'status' => sanitizeInput($putData['status'] ?? 'Open'),
        'recurring' => isset($putData['recurring']) ? 1 : 0
    ];
    
    // Validate data
    $errors = validateLogEntry($data);
    if (!empty($errors)) {
        errorResponse(implode(', ', $errors));
    }
    
    try {
        $sql = "UPDATE support_logs SET
            client_ref = ?, plugin_name = ?, plugin_version = ?, wp_version = ?,
            wc_version = ?, issue_category = ?, issue_summary = ?, detailed_description = ?,
            steps_reproduce = ?, errors_logs = ?, troubleshooting_steps = ?,
            resolution = ?, time_spent = ?, escalated = ?, status = ?, recurring = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['client_ref'], $data['plugin_name'], $data['plugin_version'],
            $data['wp_version'], $data['wc_version'], $data['issue_category'],
            $data['issue_summary'], $data['detailed_description'], $data['steps_reproduce'],
            $data['errors_logs'], $data['troubleshooting_steps'], $data['resolution'],
            $data['time_spent'], $data['escalated'], $data['status'], $data['recurring'], $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            successResponse(null, 'Log entry updated successfully');
        } else {
            errorResponse('Log entry not found', 404);
        }
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            errorResponse('A log entry with this Client Reference ID already exists');
        } else {
            errorResponse('Database error: ' . $e->getMessage(), 500);
        }
    }
}

function handleDeleteRequest() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $deleteData);
    
    $id = intval($deleteData['id'] ?? 0);
    if ($id <= 0) {
        errorResponse('Invalid log ID');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM support_logs WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            successResponse(null, 'Log entry deleted successfully');
        } else {
            errorResponse('Log entry not found', 404);
        }
        
    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}
?>
