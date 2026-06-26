<?php
// ─── Timezone — Sri Lanka ──────────────────────────────────────
date_default_timezone_set('Asia/Colombo');

// ─── Database Configuration ───────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'restopos');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ─── App Settings ─────────────────────────────────────────────
define('APP_NAME',    'RestoPOS Sri Lanka');
define('APP_VERSION', '1.0.0');
define('CURRENCY',    'Rs.');

// ─── Role Permissions ─────────────────────────────────────────
// kitchen  → Kitchen Display only (for kitchen staff logins)
// cashier  → dashboard, pos, bills, sales, inventory, expenses, debtors, reports (limited), reservations, kds
// manager  → all except settings
// admin    → everything
define('ROLE_PAGES', [
    'kitchen' => ['kds'],
    'cashier' => ['dashboard','pos','bills','sales','inventory','expenses','debtors',
                  'reports','reservations','kds','online_orders'],
    'manager' => ['dashboard','pos','bills','sales','inventory','expenses','debtors',
                  'payroll','banking','menu','promotions','employees','reports','reservations',
                  'activity_log','kds','online_orders','menu_qr'],
    'admin'   => ['dashboard','pos','bills','sales','inventory','expenses','debtors',
                  'payroll','banking','menu','promotions','employees','reports','settings',
                  'reservations','activity_log','kds','online_orders','menu_qr'],
]);

// Reports tabs cashier CANNOT see
define('CASHIER_BLOCKED_REPORT_TABS', ['payroll','attendance']);

// ─── PDO Connection ───────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;padding:20px;color:red">Database connection failed: ' . $e->getMessage() . '</div>');
        }
    }
    return $pdo;
}

// ─── Session ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user']);
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function userRole(): string {
    return strtolower($_SESSION['user']['role'] ?? 'cashier');
}

// ─── Auth Guards ──────────────────────────────────────────────
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * Call at top of each module with the page key.
 * If cashier tries to access a restricted page → redirect with error.
 */
function requireAccess(string $page): void {
    requireLogin();
    $role = userRole();
    $allowed = ROLE_PAGES[$role] ?? ROLE_PAGES['cashier'];
    if (!in_array($page, $allowed)) {
        // Redirect to the first page this role IS allowed to see,
        // to avoid redirect loops for restricted roles (e.g. Kitchen Boy
        // who has no dashboard access at all).
        $fallback = $allowed[0] ?? 'dashboard';
        header('Location: ' . $fallback . '.php?access_denied=1');
        exit;
    }
}

function canAccess(string $page): bool {
    $role = userRole();
    $allowed = ROLE_PAGES[$role] ?? ROLE_PAGES['cashier'];
    return in_array($page, $allowed);
}

/**
 * Returns true if the current user's role is blocked from viewing
 * a specific Reports tab (e.g. cashier cannot see Payroll or Attendance).
 */
function reportTabBlocked(string $tab): bool {
    if (userRole() === 'cashier' && in_array($tab, CASHIER_BLOCKED_REPORT_TABS)) {
        return true;
    }
    return false;
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
    $prefix = 'B-' . date('Ymd');
    $today  = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM bills WHERE DATE(created_at)=?");
    $stmt->execute([$today]);
    $row  = $stmt->fetch();
    $num  = str_pad(($row['cnt'] + 1), 4, '0', STR_PAD_LEFT);
    $billNo = $prefix . '-' . $num;
    // Collision check — use microseconds as fallback
    $check = $db->prepare("SELECT id FROM bills WHERE bill_no = ?");
    $check->execute([$billNo]);
    if ($check->fetch()) {
        $micro  = substr(str_replace('.', '', (string)microtime(true)), -6);
        $billNo = $prefix . '-' . $num . '-' . $micro;
    }
    return $billNo;
}

/**
 * Records an entry in the activity log. Silently does nothing if the
 * activity_log table doesn't exist yet (e.g. before migration is run),
 * so it never breaks the calling page.
 */
function logActivity(string $action, string $module, string $details = ''): void {
    try {
        $db = getDB();
        $user = currentUser();
        $stmt = $db->prepare("INSERT INTO activity_log(user_id,user_name,action,module,details,ip_address) VALUES(?,?,?,?,?,?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $user['name'] ?? 'Unknown',
            $action,
            $module,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Exception $e) {
        // Table may not exist yet if migration hasn't run — fail silently
    }
}
