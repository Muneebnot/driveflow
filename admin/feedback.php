<?php
// admin/feedback.php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$feedbacks = $db->query("SELECT f.*, u.full_name, v.vehicle_name FROM feedback f JOIN users u ON f.user_id=u.id LEFT JOIN vehicles v ON f.vehicle_id=v.id ORDER BY f.created_at DESC")->fetchAll();
$avgRating = $db->query("SELECT ROUND(AVG(rating),1) FROM feedback")->fetchColumn();

$pageTitle = 'Feedback'; $breadcrumb = 'Customer Reviews';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback – DriveFlow Admin</title>
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
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h4 class="mb-1">Customer Feedback</h4>
        <p style="color:var(--text-muted);font-size:13px;margin:0;"><?= count($feedbacks) ?> reviews · Avg: <?= $avgRating ?> ★</p></div>
      </div>
      <div class="row g-4">
        <?php if (empty($feedbacks)): ?>
          <div class="col-12"><div class="empty-state"><i class="bi bi-chat-square"></i><h5>No feedback yet</h5></div></div>
        <?php else: ?>
          <?php foreach ($feedbacks as $f): ?>
          <div class="col-md-6 col-lg-4">
            <div class="glass-card">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:34px;height:34px;background:var(--gradient-1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;"><?= strtoupper(substr($f['full_name'],0,1)) ?></div>
                  <div><strong style="font-size:13px;"><?= clean($f['full_name']) ?></strong><?php if($f['vehicle_name']): ?><br><small style="color:var(--text-muted);font-size:11px;"><?= clean($f['vehicle_name']) ?></small><?php endif; ?></div>
                </div>
                <div>
                  <?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$f['rating'] ? '-fill' : '' ?> text-warning" style="font-size:12px;"></i><?php endfor; ?>
                </div>
              </div>
              <p style="color:var(--text-secondary);font-size:13px;font-style:italic;margin-bottom:8px;">"<?= clean($f['message']) ?>"</p>
              <small style="color:var(--text-muted);font-size:11px;"><?= date('M d, Y', strtotime($f['created_at'])) ?></small>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>