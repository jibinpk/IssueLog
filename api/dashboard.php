<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize session and check authentication
initializeSession();
requireAuth();

$pdo = getDatabase();

try {
    // Get basic statistics
    $stats = [];
    
    // Total logs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_logs");
    $stats['total_logs'] = $stmt->fetch()['total'];
    
    // Status counts
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM support_logs GROUP BY status");
    $statusCounts = $stmt->fetchAll();
    
    $stats['open_logs'] = 0;
    $stats['resolved_logs'] = 0;
    $stats['escalated_logs'] = 0;
    
    foreach ($statusCounts as $row) {
        $key = strtolower($row['status']) . '_logs';
        $stats[$key] = $row['count'];
    }
    
    // Recurring issues count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_logs WHERE recurring_issue = 'Yes'");
    $stats['recurring_logs'] = $stmt->fetch()['count'];
    
    // Average time spent
    $stmt = $pdo->query("SELECT AVG(time_spent) as avg_time FROM support_logs WHERE time_spent > 0");
    $avgTime = $stmt->fetch()['avg_time'];
    $stats['avg_time'] = $avgTime ? round($avgTime, 1) : 0;
    
    // Chart data
    $chartData = [];
    
    // Issue categories chart
    $stmt = $pdo->query("SELECT concern_area, COUNT(*) as count FROM support_logs GROUP BY concern_area ORDER BY count DESC");
    $chartData['categories'] = $stmt->fetchAll();
    
    // Plugins chart
    $stmt = $pdo->query("SELECT plugin_name, COUNT(*) as count FROM support_logs GROUP BY plugin_name ORDER BY count DESC");
    $chartData['plugins'] = $stmt->fetchAll();
    
    // Issues over time (last 30 days)
    $stmt = $pdo->query("
        SELECT DATE(date_submitted) as date, COUNT(*) as count 
        FROM support_logs 
        WHERE date_submitted >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(date_submitted) 
        ORDER BY date
    ");
    $chartData['time_series'] = $stmt->fetchAll();
    
    // Recurring vs non-recurring
    $stmt = $pdo->query("
        SELECT 
            CASE WHEN recurring_issue = 'Yes' THEN 'Recurring' ELSE 'Non-recurring' END as type,
            COUNT(*) as count 
        FROM support_logs 
        GROUP BY recurring_issue
    ");
    $chartData['recurring'] = $stmt->fetchAll();
    
    successResponse([
        'stats' => $stats,
        'charts' => $chartData
    ]);
    
} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
?>
