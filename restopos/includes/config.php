<?php
// ─── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'restopos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── App Settings ─────────────────────────────────────────────
define('APP_NAME', 'RestoPOS Sri Lanka');
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'Rs.');

// ─── PDO Connection ───────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ─── Session & Auth ───────────────────────────────────────────
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

// ─── Helpers ──────────────────────────────────────────────────
function fmt(float $n): string {
    return CURRENCY . ' ' . number_format($n, 2);
}

function getSetting(string $key, string $default = ''): string {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function generateBillNo(): string {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM bills WHERE DATE(created_at)=CURDATE()");
    $row = $stmt->fetch();
    $num = str_pad(($row['cnt'] + 1), 4, '0', STR_PAD_LEFT);
    return 'B-' . date('Ymd') . '-' . $num;
}
?>
