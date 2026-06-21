<?php
requireLogin();
$user    = currentUser();
$role    = userRole();
$bizName = getSetting('business_name', 'RestoPOS');
$activePage = $activePage ?? '';

// Role badge color
$roleBadge = match($role) {
    'admin'   => ['color'=>'var(--accent)', 'label'=>'Admin'],
    'manager' => ['color'=>'var(--blue)',   'label'=>'Manager'],
    'kitchen' => ['color'=>'var(--blue)',   'label'=>'Kitchen Boy'],
    'cashier' => ['color'=>'var(--green)',  'label'=>'Cashier'],
    default   => ['color'=>'var(--muted)',  'label'=>ucfirst($role ?: 'Unknown')],
};

// Nav items — only show what this role can access
$navItems = [
    ['page'=>'dashboard',  'icon'=>'⬡',    'label'=>'Dashboard'],
    ['page'=>'pos',        'icon'=>'🧾',   'label'=>'POS / Billing'],
    ['page'=>'bills',      'icon'=>'📄',   'label'=>'Bill History'],
    ['page'=>'kds',        'icon'=>'🔥',   'label'=>'Kitchen Display'],
    ['page'=>'reservations','icon'=>'📅',  'label'=>'Reservations'],
    ['page'=>'sales',      'icon'=>'📊',   'label'=>'Sales Reports'],
    ['page'=>'inventory',  'icon'=>'📦',   'label'=>'Inventory'],
    ['page'=>'expenses',   'icon'=>'💸',   'label'=>'Expenses'],
    ['page'=>'debtors',    'icon'=>'🏦',   'label'=>'Debtors'],
    ['page'=>'payroll',    'icon'=>'👥',   'label'=>'Payroll'],
    ['page'=>'banking',    'icon'=>'🏛',   'label'=>'Cash & Banking'],
    ['page'=>'menu',       'icon'=>'🍽',   'label'=>'Menu Manager'],
    ['page'=>'promotions', 'icon'=>'🎉',   'label'=>'Promotions'],
    ['page'=>'employees',  'icon'=>'🧑‍💼', 'label'=>'Employees'],
    ['page'=>'reports',    'icon'=>'📋',   'label'=>'Reports'],
    ['page'=>'activity_log','icon'=>'🕓',  'label'=>'Activity Log'],
    ['page'=>'settings',   'icon'=>'⚙',   'label'=>'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'RestoPOS') ?> — <?= htmlspecialchars($bizName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🍛</div>
        <div class="brand-text">
            <span class="brand-name">RestoPOS</span>
            <span class="brand-sub">Sri Lanka</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $nav):
            if (!canAccess($nav['page'])) continue; // hide restricted pages
        ?>
        <a href="<?= $nav['page'] ?>.php"
           class="nav-item <?= $activePage===$nav['page']?'active':'' ?>">
            <span class="nav-icon"><?= $nav['icon'] ?></span>
            <span class="nav-label"><?= $nav['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar">👤</div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
            <span class="user-role" style="color:<?= $roleBadge['color'] ?>"><?= $roleBadge['label'] ?></span>
        </div>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrap" id="mainWrap">
    <header class="topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-right">
            <span class="topbar-time" id="clock"></span>
            <!-- Role badge in topbar -->
            <span style="padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;
                         background:<?= $roleBadge['color'] ?>22;
                         color:<?= $roleBadge['color'] ?>;
                         border:1px solid <?= $roleBadge['color'] ?>44">
                <?= $roleBadge['label'] ?>
            </span>
            <span class="badge badge-green" style="font-size:11px">● Live</span>
            <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </header>

    <?php if (isset($_GET['access_denied'])): ?>
    <div style="margin:16px 24px">
        <div class="alert alert-danger" style="display:flex;align-items:center;gap:12px">
            <span style="font-size:20px">🔒</span>
            <div>
                <strong>Access Restricted</strong><br>
                <span style="font-size:13px">Your account (<?= $roleBadge['label'] ?>) does not have permission to access that page.</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="page-content">
