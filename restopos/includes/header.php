<?php
requireLogin();
$user = currentUser();
$bizName = getSetting('business_name', 'RestoPOS');
$activePage = $activePage ?? '';
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
        <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>"><span class="nav-icon">⬡</span><span class="nav-label">Dashboard</span></a>
        <a href="pos.php"       class="nav-item <?= $activePage==='pos'?'active':'' ?>"><span class="nav-icon">🧾</span><span class="nav-label">POS / Billing</span></a>
        <a href="sales.php"     class="nav-item <?= $activePage==='sales'?'active':'' ?>"><span class="nav-icon">📊</span><span class="nav-label">Sales Reports</span></a>
        <a href="inventory.php" class="nav-item <?= $activePage==='inventory'?'active':'' ?>"><span class="nav-icon">📦</span><span class="nav-label">Inventory</span></a>
        <a href="expenses.php"  class="nav-item <?= $activePage==='expenses'?'active':'' ?>"><span class="nav-icon">💸</span><span class="nav-label">Expenses</span></a>
        <a href="debtors.php"   class="nav-item <?= $activePage==='debtors'?'active':'' ?>"><span class="nav-icon">🏦</span><span class="nav-label">Debtors</span></a>
        <a href="payroll.php"   class="nav-item <?= $activePage==='payroll'?'active':'' ?>"><span class="nav-icon">👥</span><span class="nav-label">Payroll</span></a>
        <a href="banking.php"   class="nav-item <?= $activePage==='banking'?'active':'' ?>"><span class="nav-icon">🏛</span><span class="nav-label">Cash & Banking</span></a>
        <a href="menu.php"      class="nav-item <?= $activePage==='menu'?'active':'' ?>"><span class="nav-icon">🍽</span><span class="nav-label">Menu Manager</span></a>
        <a href="employees.php" class="nav-item <?= $activePage==='employees'?'active':'' ?>"><span class="nav-icon">🧑‍💼</span><span class="nav-label">Employees</span></a>
        <a href="reports.php"   class="nav-item <?= $activePage==='reports'?'active':'' ?>"><span class="nav-icon">📋</span><span class="nav-label">Reports</span></a>
        <a href="settings.php"  class="nav-item <?= $activePage==='settings'?'active':'' ?>"><span class="nav-icon">⚙</span><span class="nav-label">Settings</span></a>
    </nav>
    <div class="sidebar-user">
        <div class="user-avatar">👤</div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></span>
            <span class="user-role"><?= htmlspecialchars(ucfirst($user['role'] ?? 'admin')) ?></span>
        </div>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrap" id="mainWrap">
    <header class="topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-right">
            <span class="topbar-time" id="clock"></span>
            <span class="badge badge-green">● Live</span>
            <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </header>
    <main class="page-content">
