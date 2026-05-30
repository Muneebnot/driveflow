<?php
// admin/activity_logs.php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$search = trim($_GET['search'] ?? '');
$where = '1=1'; $params = [];
if ($search) { $where .= " AND (al.activity LIKE ? OR u.full_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id WHERE $where ORDER BY al.created_at DESC LIMIT 200");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Activity Logs'; $breadcrumb = 'Logs';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs – DriveFlow Admin</title>
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
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div><h4 class="mb-1">Activity Logs</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">Last 200 system activities</p></div>
      </div>
      <form method="GET" class="filter-bar mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search activity or user..." value="<?= clean($search) ?>">
        <button type="submit" class="btn btn-glass"><i class="bi bi-search me-1"></i>Search</button>
        <?php if ($search): ?><a href="<?= SITE_URL ?>/admin/activity_logs.php" class="btn btn-glass"><i class="bi bi-x"></i></a><?php endif; ?>
      </form>
      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($logs)): ?>
            <div class="empty-state"><i class="bi bi-journal-text"></i><h5>No logs found</h5></div>
          <?php else: ?>
          <table class="df-table">
            <thead><tr><th>#</th><th>User</th><th>Activity</th><th>IP Address</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td style="color:var(--text-muted);"><?= $log['id'] ?></td>
              <td style="font-size:13px;"><?= clean($log['full_name'] ?? 'System') ?></td>
              <td style="font-size:13px;"><?= clean($log['activity']) ?></td>
              <td><code style="font-size:11px;color:var(--text-muted);"><?= clean($log['ip_address'] ?? '—') ?></code></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>