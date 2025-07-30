<?php
// Session configuration - ensure this runs before any output
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Set session name
    session_name('SUPPORT_LOG_SESSION');
    
    // Set session lifetime (4 hours)
    ini_set('session.gc_maxlifetime', 14400);
    session_set_cookie_params([
        'lifetime' => 14400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Custom session handler using database
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function open($save_path, $session_name): bool {
        return true;
    }
    
    public function close(): bool {
        return true;
    }
    
    public function read($id): string {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ? AND access >= ?");
        $stmt->execute([$id, time() - 14400]);
        $result = $stmt->fetch();
        return $result ? $result['data'] : '';
    }
    
    public function write($id, $data): bool {
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)");
        return $stmt->execute([$id, time(), $data]);
    }
    
    public function destroy($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function gc($maxlifetime): int {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE access < ?");
        $stmt->execute([time() - $maxlifetime]);
        return $stmt->rowCount();
    }
}
?>
