<?php
// includes/admin_header.php
// Top header bar for admin pages
$db = getDB();
$unreadNotifs = $db->query("SELECT COUNT(*) FROM notifications WHERE status='unread'")->fetchColumn();
?>
<header class="top-header">
  <div class="d-flex align-items-center gap-3">
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
      <i class="bi bi-list"></i>
    </button>
    <div class="page-title-wrap">
      <h4><?= $pageTitle ?? 'Dashboard' ?></h4>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/admin/dashboard.php" style="color:var(--text-muted);text-decoration:none;">Admin</a></li>
        <?php if (isset($breadcrumb)): ?>
          <li class="breadcrumb-item"><?= $breadcrumb ?></li>
        <?php endif; ?>
      </ol>
    </div>
  </div>
  <div class="header-actions">
    <a href="<?= SITE_URL ?>/admin/notifications.php" class="header-btn" title="Notifications">
      <i class="bi bi-bell-fill"></i>
      <?php if ($unreadNotifs > 0): ?><span class="notif-dot"></span><?php endif; ?>
    </a>
    <a href="<?= SITE_URL ?>/index.php" class="header-btn" title="View Website" target="_blank">
      <i class="bi bi-box-arrow-up-right"></i>
    </a>
    <a href="<?= SITE_URL ?>/logout.php" class="header-btn" title="Logout">
      <i class="bi bi-power"></i>
    </a>
  </div>
</header>