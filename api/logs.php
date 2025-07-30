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
        $sql .= " AND (query_title LIKE ? OR plugin_name LIKE ? OR description LIKE ?)";
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
        $sql .= " AND concern_area = ?";
        $params[] = $category;
    }
    
    if ($recurring === 'Yes') {
        $sql .= " AND recurring_issue = 'Yes'";
    } elseif ($recurring === 'No') {
        $sql .= " AND recurring_issue = 'No'";
    }
    
    if ($escalated === 'Yes') {
        $sql .= " AND escalated_to_dev = 'Yes'";
    } elseif ($escalated === 'No') {
        $sql .= " AND escalated_to_dev = 'No'";
    }
    
    $sql .= " ORDER BY date_submitted DESC";
    
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
        'plugin_name' => sanitizeInput($_POST['plugin_name'] ?? ''),
        'issue_type' => sanitizeInput($_POST['issue_type'] ?? ''),
        'concern_area' => sanitizeInput($_POST['concern_area'] ?? ''),
        'query_title' => sanitizeInput($_POST['query_title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'steps_reproduce' => sanitizeInput($_POST['steps_reproduce'] ?? ''),
        'error_logs' => sanitizeInput($_POST['error_logs'] ?? ''),
        'wp_version' => sanitizeInput($_POST['wp_version'] ?? ''),
        'wc_version' => sanitizeInput($_POST['wc_version'] ?? ''),
        'plugin_version' => sanitizeInput($_POST['plugin_version'] ?? ''),
        'assigned_agent' => sanitizeInput($_POST['assigned_agent'] ?? ''),
        'time_spent' => intval($_POST['time_spent'] ?? 0),
        'recurring_issue' => sanitizeInput($_POST['recurring_issue'] ?? 'No'),
        'escalated_to_dev' => sanitizeInput($_POST['escalated_to_dev'] ?? 'No'),
        'status' => sanitizeInput($_POST['status'] ?? 'Open'),
        'resolution_notes' => sanitizeInput($_POST['resolution_notes'] ?? '')
    ];
    
    // Validate data
    $errors = validateLogEntry($data);
    if (!empty($errors)) {
        errorResponse(implode(', ', $errors));
    }
    
    try {
        $sql = "INSERT INTO support_logs (
            plugin_name, issue_type, concern_area, query_title, description,
            steps_reproduce, error_logs, wp_version, wc_version, plugin_version,
            assigned_agent, time_spent, recurring_issue, escalated_to_dev,
            status, resolution_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['plugin_name'], $data['issue_type'], $data['concern_area'],
            $data['query_title'], $data['description'], $data['steps_reproduce'],
            $data['error_logs'], $data['wp_version'], $data['wc_version'],
            $data['plugin_version'], $data['assigned_agent'], $data['time_spent'],
            $data['recurring_issue'], $data['escalated_to_dev'], $data['status'],
            $data['resolution_notes']
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
        'plugin_name' => sanitizeInput($putData['plugin_name'] ?? ''),
        'issue_type' => sanitizeInput($putData['issue_type'] ?? ''),
        'concern_area' => sanitizeInput($putData['concern_area'] ?? ''),
        'query_title' => sanitizeInput($putData['query_title'] ?? ''),
        'description' => sanitizeInput($putData['description'] ?? ''),
        'steps_reproduce' => sanitizeInput($putData['steps_reproduce'] ?? ''),
        'error_logs' => sanitizeInput($putData['error_logs'] ?? ''),
        'wp_version' => sanitizeInput($putData['wp_version'] ?? ''),
        'wc_version' => sanitizeInput($putData['wc_version'] ?? ''),
        'plugin_version' => sanitizeInput($putData['plugin_version'] ?? ''),
        'assigned_agent' => sanitizeInput($putData['assigned_agent'] ?? ''),
        'time_spent' => intval($putData['time_spent'] ?? 0),
        'recurring_issue' => sanitizeInput($putData['recurring_issue'] ?? 'No'),
        'escalated_to_dev' => sanitizeInput($putData['escalated_to_dev'] ?? 'No'),
        'status' => sanitizeInput($putData['status'] ?? 'Open'),
        'resolution_notes' => sanitizeInput($putData['resolution_notes'] ?? '')
    ];
    
    // Validate data
    $errors = validateLogEntry($data);
    if (!empty($errors)) {
        errorResponse(implode(', ', $errors));
    }
    
    try {
        $sql = "UPDATE support_logs SET
            plugin_name = ?, issue_type = ?, concern_area = ?, query_title = ?,
            description = ?, steps_reproduce = ?, error_logs = ?, wp_version = ?,
            wc_version = ?, plugin_version = ?, assigned_agent = ?, time_spent = ?,
            recurring_issue = ?, escalated_to_dev = ?, status = ?, resolution_notes = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['plugin_name'], $data['issue_type'], $data['concern_area'],
            $data['query_title'], $data['description'], $data['steps_reproduce'],
            $data['error_logs'], $data['wp_version'], $data['wc_version'],
            $data['plugin_version'], $data['assigned_agent'], $data['time_spent'],
            $data['recurring_issue'], $data['escalated_to_dev'], $data['status'],
            $data['resolution_notes'], $id
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
