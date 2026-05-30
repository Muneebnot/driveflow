<?php
// admin/payments.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();
$filterStatus = $_GET['status'] ?? '';
$where = '1=1'; $params = [];
if ($filterStatus) { $where .= " AND p.payment_status=?"; $params[] = $filterStatus; }

$stmt = $db->prepare("SELECT p.*, r.start_date, r.end_date, u.full_name, v.vehicle_name FROM payments p JOIN rentals r ON p.rental_id=r.id JOIN users u ON r.user_id=u.id JOIN vehicles v ON r.vehicle_id=v.id WHERE $where ORDER BY p.payment_date DESC");
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totalRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed'")->fetchColumn();
$pendingAmount = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Pending'")->fetchColumn();

$pageTitle = 'Payments'; $breadcrumb = 'Payment Records';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments – DriveFlow Admin</title>
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
      <div class="mb-4"><h4 class="mb-1">Payments</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">All payment transactions</p></div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="stat-card green">
            <div class="stat-icon green"><i class="bi bi-credit-card-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value" style="font-size:1.3rem;">PKR <?= number_format($totalRevenue,0) ?></span>
              <span class="stat-label">Total Revenue Collected</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card orange">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
              <span class="stat-value" style="font-size:1.3rem;">PKR <?= number_format($pendingAmount,0) ?></span>
              <span class="stat-label">Pending Payments</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card blue">
            <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
            <div class="stat-info">
              <span class="stat-value"><?= count($payments) ?></span>
              <span class="stat-label">Total Transactions</span>
            </div>
          </div>
        </div>
      </div>

      <form method="GET" class="filter-bar mb-4">
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <?php foreach (['Completed','Pending','Failed','Refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus===$s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-glass"><i class="bi bi-funnel me-1"></i>Filter</button>
        <?php if ($filterStatus): ?><a href="<?= SITE_URL ?>/admin/payments.php" class="btn btn-glass"><i class="bi bi-x"></i></a><?php endif; ?>
      </form>

      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($payments)): ?>
            <div class="empty-state"><i class="bi bi-credit-card"></i><h5>No payments found</h5></div>
          <?php else: ?>
          <table class="df-table">
            <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Transaction ID</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td style="color:var(--text-muted);">#<?= $p['id'] ?></td>
              <td style="font-size:13px;"><?= clean($p['full_name']) ?></td>
              <td style="font-size:13px;"><?= clean($p['vehicle_name']) ?></td>
              <td style="color:var(--accent-cyan);font-weight:700;"><?= formatCurrency($p['amount']) ?></td>
              <td style="font-size:13px;"><?= $p['payment_method'] ?></td>
              <td>
                <?php
                $colors = ['Completed'=>'active','Pending'=>'pending','Failed'=>'cancelled','Refunded'=>'approved'];
                echo '<span class="badge-' . ($colors[$p['payment_status']] ?? 'pending') . '">' . $p['payment_status'] . '</span>';
                ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted);"><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
              <td><code style="font-size:11px;color:var(--text-muted);"><?= $p['transaction_id'] ?? '—' ?></code></td>
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