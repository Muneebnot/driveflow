<?php
// admin/customers.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();
$search = trim($_GET['search'] ?? '');
$where = "role='customer'";
$params = [];
if ($search) { $where .= " AND (full_name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM rentals WHERE user_id=u.id) as total_rentals, (SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN rentals r ON p.rental_id=r.id WHERE r.user_id=u.id AND p.payment_status='Completed') as total_spent FROM users u WHERE $where ORDER BY u.created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers'; $breadcrumb = 'Customer Management';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers – DriveFlow Admin</title>
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
        <div><h4 class="mb-1">Customers</h4><p style="color:var(--text-muted);font-size:13px;margin:0;"><?= count($customers) ?> registered customers</p></div>
      </div>
      <form method="GET" class="filter-bar mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search name or email..." value="<?= clean($search) ?>">
        <button type="submit" class="btn btn-glass"><i class="bi bi-search me-1"></i>Search</button>
        <?php if ($search): ?><a href="<?= SITE_URL ?>/admin/customers.php" class="btn btn-glass"><i class="bi bi-x"></i> Clear</a><?php endif; ?>
      </form>
      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($customers)): ?>
            <div class="empty-state"><i class="bi bi-people"></i><h5>No customers found</h5></div>
          <?php else: ?>
          <table class="df-table">
            <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Rentals</th><th>Total Spent</th><th>Joined</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div style="width:36px;height:36px;background:var(--gradient-1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                    <?= strtoupper(substr($c['full_name'],0,1)) ?>
                  </div>
                  <strong style="font-size:13px;"><?= clean($c['full_name']) ?></strong>
                </div>
              </td>
              <td style="font-size:13px;"><?= clean($c['email']) ?></td>
              <td style="font-size:13px;"><?= clean($c['phone'] ?? '—') ?></td>
              <td><span style="background:rgba(59,130,246,0.1);color:var(--accent-blue);padding:2px 10px;border-radius:4px;font-size:12px;"><?= $c['total_rentals'] ?> rentals</span></td>
              <td style="color:var(--accent-cyan);font-weight:600;"><?= formatCurrency($c['total_spent']) ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
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