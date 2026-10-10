<?php
/**
 * Shared Header Layout Template
 * ApexFinance - Red, Black, White UI
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/CSRF.php';

Auth::requireLogin();
$user = Auth::user();
$currentRole = Auth::role();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?> - Financial Management System</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
  <div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand-badge">AF</div>
        <div>
          <div class="brand-title">ApexFinance</div>
          <div class="brand-subtitle">Smart Loan System</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
          📊 Dashboard
        </a>

        <?php if ($currentRole === 'customer'): ?>
          <a href="<?= APP_URL ?>/loans.php" class="nav-item <?= $currentPage === 'loans.php' ? 'active' : '' ?>">
            💳 My Loans
          </a>
          <a href="<?= APP_URL ?>/payments.php" class="nav-item <?= $currentPage === 'payments.php' ? 'active' : '' ?>">
            🧾 Payment Records
          </a>
          <a href="<?= APP_URL ?>/profile.php" class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            👤 My Profile
          </a>
        <?php endif; ?>

        <?php if ($currentRole === 'officer' || $currentRole === 'admin'): ?>
          <a href="<?= APP_URL ?>/customers.php" class="nav-item <?= $currentPage === 'customers.php' ? 'active' : '' ?>">
            👥 Customer Directory
          </a>
          <a href="<?= APP_URL ?>/loans.php" class="nav-item <?= $currentPage === 'loans.php' ? 'active' : '' ?>">
            ⚖️ Loan Applications
          </a>
          <a href="<?= APP_URL ?>/payments.php" class="nav-item <?= $currentPage === 'payments.php' ? 'active' : '' ?>">
            💰 Record Payments
          </a>
          <a href="<?= APP_URL ?>/reports.php" class="nav-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
            📈 Monthly Reports
          </a>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
          <a href="<?= APP_URL ?>/users.php" class="nav-item <?= $currentPage === 'users.php' ? 'active' : '' ?>">
            ⚙️ Staff & System Users
          </a>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="user-pill">
          <div>
            <div style="color:#FFF; font-weight:700;"><?= e($user['name']) ?></div>
            <div style="font-size:11px; text-transform:uppercase; color:#DC2626; font-weight:700;"><?= e($user['role']) ?></div>
          </div>
          <a href="<?= APP_URL ?>/logout.php" style="color:#9CA3AF; text-decoration:none; font-size:18px;" title="Logout">🚪</a>
        </div>
      </div>
    </aside>

    <!-- Main Workspace Container -->
    <main class="main-content">
      <header class="topbar">
        <div class="topbar-title">
          <?= match($currentPage) {
            'dashboard.php' => 'Overview Dashboard',
            'loans.php'     => 'Loan Management & Applications',
            'payments.php'  => 'Payment Records & Collection',
            'customers.php' => 'Customer Directory & Search',
            'reports.php'   => 'Monthly Loan & Collection Reports',
            'profile.php'   => 'Customer Profile',
            'users.php'     => 'System Staff & User Management',
            default         => 'ApexFinance System'
          } ?>
        </div>
        <div style="display:flex; align-items:center; gap:16px;">
          <span class="badge badge-pending" style="font-size:12px; padding:6px 14px;">
            <?= e($user['account_number']) ?>
          </span>
          <a href="<?= APP_URL ?>/logout.php" class="btn btn-primary btn-sm">Logout</a>
        </div>
      </header>

      <div class="content-body">
        <?php if (hasFlash('danger')): ?>
          <div class="alert alert-danger"><?= getFlash('danger') ?></div>
        <?php endif; ?>
        <?php if (hasFlash('success')): ?>
          <div class="alert alert-success"><?= getFlash('success') ?></div>
        <?php endif; ?>
        <?php if (hasFlash('warning')): ?>
          <div class="alert alert-warning"><?= getFlash('warning') ?></div>
        <?php endif; ?>
