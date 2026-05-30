<?php
// admin/notifications.php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

// Mark all read
if (isset($_GET['mark_all'])) {
    $db->query("UPDATE notifications SET status='read' WHERE status='unread'");
    setFlash('success','All notifications marked as read.');
    redirect(SITE_URL.'/admin/notifications.php');
}

$notifs = $db->query("SELECT n.*, u.full_name FROM notifications n LEFT JOIN users u ON n.user_id=u.id ORDER BY n.created_at DESC")->fetchAll();
$pageTitle = 'Notifications'; $breadcrumb = 'Notifications';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications – DriveFlow Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet"><link href="../assets/css/admin.css" rel="stylesheet">
</head><body>
<div class="admin-wrapper">
  <?php include '../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/admin_header.php'; ?>
    <div class="content-area">
      <?php $flash = getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div><?php endif; ?>

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h4 class="mb-1">Notifications</h4><p style="color:var(--text-muted);font-size:13px;margin:0;"><?= count($notifs) ?> total</p></div>
        <a href="?mark_all=1" class="btn btn-glass btn-sm"><i class="bi bi-check2-all me-1"></i>Mark All Read</a>
      </div>

      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($notifs)): ?>
            <div class="empty-state"><i class="bi bi-bell"></i><h5>No notifications</h5></div>
          <?php else: ?>
            <ul class="activity-list">
              <?php foreach ($notifs as $n): ?>
              <li class="activity-item" style="<?= $n['status']==='unread' ? 'background:rgba(59,130,246,0.04);border-radius:10px;padding:12px;margin-bottom:4px;' : '' ?>">
                <div class="activity-icon" style="<?= $n['status']==='unread' ? 'background:rgba(59,130,246,0.2);' : '' ?>">
                  <i class="bi bi-bell-fill"></i>
                </div>
                <div style="flex:1;">
                  <div style="font-size:13px;font-weight:<?= $n['status']==='unread' ? '600' : '400' ?>;color:var(--text-primary);">
                    <?= clean($n['title']) ?>
                    <?php if ($n['status']==='unread'): ?>
                      <span style="background:var(--accent-blue);color:#fff;font-size:9px;padding:1px 6px;border-radius:50px;margin-left:6px;vertical-align:middle;">NEW</span>
                    <?php endif; ?>
                  </div>
                  <div class="activity-text"><?= clean($n['message']) ?></div>
                  <div class="activity-time">
                    <?php if ($n['full_name']): ?><span class="me-2"><i class="bi bi-person me-1"></i><?= clean($n['full_name']) ?></span><?php endif; ?>
                    <i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>