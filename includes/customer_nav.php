<?php
// includes/customer_nav.php
$db = getDB();
$uid = $_SESSION['user_id'];
$unread = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND status='unread'");
$unread->execute([$uid]);
$unreadCount = $unread->fetchColumn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="cust-navbar" id="custNav">
  <div class="container d-flex align-items-center justify-content-between">
    <!-- Logo -->
    <a href="<?= SITE_URL ?>/customer/dashboard.php" class="navbar-brand">
      <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
      Drive<span class="brand-accent">Flow</span>
    </a>

    <!-- Desktop Nav -->
    <ul class="cust-nav-links">
      <li><a href="<?= SITE_URL ?>/customer/dashboard.php" class="<?= $currentPage==='dashboard.php' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
      <li><a href="<?= SITE_URL ?>/customer/browse.php"    class="<?= $currentPage==='browse.php' ? 'active' : '' ?>"><i class="bi bi-search"></i> Browse</a></li>
      <li><a href="<?= SITE_URL ?>/customer/my_rentals.php"class="<?= $currentPage==='my_rentals.php' ? 'active' : '' ?>"><i class="bi bi-calendar2-check"></i> My Rentals</a></li>
      <li><a href="<?= SITE_URL ?>/customer/feedback.php"  class="<?= $currentPage==='feedback.php' ? 'active' : '' ?>"><i class="bi bi-star"></i> Feedback</a></li>
    </ul>

    <!-- Right Actions -->
    <div class="cust-nav-actions">
      <a href="<?= SITE_URL ?>/customer/dashboard.php" class="cust-notif-btn" title="Notifications">
        <i class="bi bi-bell-fill"></i>
        <?php if ($unreadCount > 0): ?><span class="notif-dot"></span><?php endif; ?>
      </a>
      <div class="dropdown">
        <button class="cust-user-btn dropdown-toggle" data-bs-toggle="dropdown">
          <div class="cust-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
          <span class="d-none d-md-inline"><?= clean(explode(' ',$_SESSION['full_name'])[0]) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="background:var(--dark-card);border:1px solid var(--glass-border);border-radius:14px;padding:8px;">
          <li><a class="dropdown-item" href="<?= SITE_URL ?>/customer/profile.php" style="color:var(--text-secondary);border-radius:8px;font-size:13px;"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><a class="dropdown-item" href="<?= SITE_URL ?>/customer/my_rentals.php" style="color:var(--text-secondary);border-radius:8px;font-size:13px;"><i class="bi bi-calendar2-check me-2"></i>My Rentals</a></li>
          <li><hr style="border-color:var(--dark-border);margin:4px 0;"></li>
          <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php" style="color:#fca5a5;border-radius:8px;font-size:13px;"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
        </ul>
      </div>
      <button class="mobile-menu-btn" onclick="toggleCustMenu()" id="custMenuBtn"><i class="bi bi-list"></i></button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div class="cust-mobile-menu" id="custMobileMenu">
    <a href="<?= SITE_URL ?>/customer/dashboard.php"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</a>
    <a href="<?= SITE_URL ?>/customer/browse.php"><i class="bi bi-search me-2"></i>Browse Vehicles</a>
    <a href="<?= SITE_URL ?>/customer/my_rentals.php"><i class="bi bi-calendar2-check me-2"></i>My Rentals</a>
    <a href="<?= SITE_URL ?>/customer/feedback.php"><i class="bi bi-star me-2"></i>Feedback</a>
    <a href="<?= SITE_URL ?>/customer/profile.php"><i class="bi bi-person me-2"></i>My Profile</a>
    <a href="<?= SITE_URL ?>/logout.php" style="color:#fca5a5;"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a>
  </div>
</nav>
<script>
function toggleCustMenu() {
  document.getElementById('custMobileMenu').classList.toggle('show');
}
</script>