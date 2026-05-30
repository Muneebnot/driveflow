<?php
// includes/admin_sidebar.php
// Reusable admin sidebar - include at top of every admin page
$currentPage = basename($_SERVER['PHP_SELF']);
$db = getDB();
$unreadNotifs = $db->query("SELECT COUNT(*) FROM notifications WHERE status='unread'")->fetchColumn();
$pendingRentals = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Pending'")->fetchColumn();
?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="adminSidebar">
  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="bi bi-lightning-charge-fill text-white"></i></div>
    <div class="logo-text">Drive<span>Flow</span></div>
  </div>

  <!-- Main Navigation -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Main</div>
    <ul class="sidebar-nav">
      <li>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="<?= $currentPage=='dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/vehicles.php" class="<?= in_array($currentPage,['vehicles.php','add_vehicle.php','edit_vehicle.php']) ? 'active' : '' ?>">
          <i class="bi bi-car-front-fill"></i> Vehicles
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/vehicle_types.php" class="<?= $currentPage=='vehicle_types.php' ? 'active' : '' ?>">
          <i class="bi bi-tags-fill"></i> Vehicle Types
        </a>
      </li>
    </ul>
  </div>

  <!-- Operations -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Operations</div>
    <ul class="sidebar-nav">
      <li>
        <a href="<?= SITE_URL ?>/admin/rentals.php" class="<?= $currentPage=='rentals.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar2-check-fill"></i> Rentals
          <?php if ($pendingRentals > 0): ?>
            <span class="sidebar-badge"><?= $pendingRentals ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/payments.php" class="<?= $currentPage=='payments.php' ? 'active' : '' ?>">
          <i class="bi bi-credit-card-fill"></i> Payments
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/customers.php" class="<?= $currentPage=='customers.php' ? 'active' : '' ?>">
          <i class="bi bi-people-fill"></i> Customers
        </a>
      </li>
    </ul>
  </div>

  <!-- Insights -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Insights</div>
    <ul class="sidebar-nav">
      <li>
        <a href="<?= SITE_URL ?>/admin/analytics.php" class="<?= $currentPage=='analytics.php' ? 'active' : '' ?>">
          <i class="bi bi-graph-up-arrow"></i> Analytics
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/feedback.php" class="<?= $currentPage=='feedback.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-square-dots-fill"></i> Feedback
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/notifications.php" class="<?= $currentPage=='notifications.php' ? 'active' : '' ?>">
          <i class="bi bi-bell-fill"></i> Notifications
          <?php if ($unreadNotifs > 0): ?>
            <span class="sidebar-badge"><?= $unreadNotifs ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= SITE_URL ?>/admin/activity_logs.php" class="<?= $currentPage=='activity_logs.php' ? 'active' : '' ?>">
          <i class="bi bi-journal-text"></i> Activity Logs
        </a>
      </li>
    </ul>
  </div>

  <!-- User Info -->
  <div class="sidebar-user">
    <div class="sidebar-user-avatar"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= clean($_SESSION['full_name']) ?></div>
      <div class="sidebar-user-role">Administrator</div>
    </div>
    <a href="<?= SITE_URL ?>/logout.php" class="sidebar-user-logout" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</aside>