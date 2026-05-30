<?php
// admin/rentals.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();

// Handle status update
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $r = $db->prepare("SELECT r.*, v.vehicle_name, u.full_name FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id JOIN users u ON r.user_id=u.id WHERE r.id=?");
    $r->execute([$id]);
    $rental = $r->fetch();
    if ($rental) {
        $db->prepare("UPDATE rentals SET rental_status='Approved' WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE vehicles SET status='Rented' WHERE id=?")->execute([$rental['vehicle_id']]);
        createNotification($rental['user_id'], 'Rental Approved!', "Your rental for {$rental['vehicle_name']} has been approved. Enjoy your drive!");
        logActivity($_SESSION['user_id'], "Approved rental #$id for {$rental['full_name']}");
        setFlash('success', 'Rental approved and vehicle marked as Rented.');
    }
    redirect(SITE_URL . '/admin/rentals.php');
}

if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $r = $db->prepare("SELECT * FROM rentals WHERE id=?");
    $r->execute([$id]);
    $rental = $r->fetch();
    if ($rental) {
        $db->prepare("UPDATE rentals SET rental_status='Completed' WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE vehicles SET status='Available' WHERE id=?")->execute([$rental['vehicle_id']]);
        // Record payment if not already
        $pay = $db->prepare("SELECT id FROM payments WHERE rental_id=?");
        $pay->execute([$id]);
        if (!$pay->fetch()) {
            $db->prepare("INSERT INTO payments (rental_id, amount, payment_method, payment_status) VALUES (?,?,'Cash','Completed')")->execute([$id, $rental['total_price']]);
        }
        logActivity($_SESSION['user_id'], "Completed rental #$id");
        setFlash('success', 'Rental marked as completed.');
    }
    redirect(SITE_URL . '/admin/rentals.php');
}

if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    $r = $db->prepare("SELECT * FROM rentals WHERE id=?");
    $r->execute([$id]);
    $rental = $r->fetch();
    if ($rental) {
        $db->prepare("UPDATE rentals SET rental_status='Cancelled' WHERE id=?")->execute([$id]);
        if (in_array($rental['rental_status'], ['Approved','Active'])) {
            $db->prepare("UPDATE vehicles SET status='Available' WHERE id=?")->execute([$rental['vehicle_id']]);
        }
        logActivity($_SESSION['user_id'], "Cancelled rental #$id");
        setFlash('warning', 'Rental cancelled.');
    }
    redirect(SITE_URL . '/admin/rentals.php');
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = '1=1';
$params = [];
if ($filterStatus) { $where .= " AND r.rental_status=?"; $params[] = $filterStatus; }
if ($search) { $where .= " AND (u.full_name LIKE ? OR v.vehicle_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT r.*, u.full_name, v.vehicle_name FROM rentals r JOIN users u ON r.user_id=u.id JOIN vehicles v ON r.vehicle_id=v.id WHERE $where ORDER BY r.created_at DESC");
$stmt->execute($params);
$rentals = $stmt->fetchAll();

$pageTitle = 'Rentals';
$breadcrumb = 'Rental Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rentals – DriveFlow Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
  <?php include '../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/admin_header.php'; ?>
    <div class="content-area">

      <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div>
      <?php endif; ?>

      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h4 class="mb-1">Rental Management</h4>
          <p style="color:var(--text-muted);font-size:13px;margin:0;"><?= count($rentals) ?> rentals found</p>
        </div>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search customer or vehicle..." value="<?= clean($search) ?>">
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <?php foreach (['Pending','Approved','Active','Completed','Cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-glass"><i class="bi bi-funnel me-1"></i>Filter</button>
        <?php if ($search || $filterStatus): ?><a href="<?= SITE_URL ?>/admin/rentals.php" class="btn btn-glass"><i class="bi bi-x"></i> Clear</a><?php endif; ?>
      </form>

      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($rentals)): ?>
            <div class="empty-state"><i class="bi bi-calendar2-x"></i><h5>No rentals found</h5><p>Adjust your filters to see results.</p></div>
          <?php else: ?>
          <table class="df-table">
            <thead><tr>
              <th>#</th><th>Customer</th><th>Vehicle</th><th>Start</th><th>End</th>
              <th>Days</th><th>Amount</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rentals as $r):
              $days = (int)((strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400);
            ?>
            <tr>
              <td style="color:var(--text-muted);">#<?= $r['id'] ?></td>
              <td><strong style="font-size:13px;"><?= clean($r['full_name']) ?></strong></td>
              <td style="font-size:13px;"><?= clean($r['vehicle_name']) ?></td>
              <td style="font-size:12px;"><?= $r['start_date'] ?></td>
              <td style="font-size:12px;"><?= $r['end_date'] ?></td>
              <td><span style="background:rgba(59,130,246,0.1);color:var(--accent-blue);padding:2px 8px;border-radius:4px;font-size:12px;"><?= $days ?>d</span></td>
              <td style="color:var(--accent-cyan);font-weight:600;"><?= formatCurrency($r['total_price']) ?></td>
              <td><span class="badge-<?= strtolower($r['rental_status']) ?>"><?= $r['rental_status'] ?></span></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($r['rental_status'] === 'Pending'): ?>
                    <a href="<?= SITE_URL ?>/admin/rentals.php?approve=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#6ee7b7;border-radius:8px;" title="Approve">
                      <i class="bi bi-check2"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (in_array($r['rental_status'], ['Approved','Active'])): ?>
                    <a href="<?= SITE_URL ?>/admin/rentals.php?complete=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.2);color:#c4b5fd;border-radius:8px;" title="Mark Complete">
                      <i class="bi bi-flag-fill"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (!in_array($r['rental_status'], ['Completed','Cancelled'])): ?>
                    <button onclick="confirmDelete('<?= SITE_URL ?>/admin/rentals.php?cancel=<?= $r['id'] ?>', 'Cancel this rental?')" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;border-radius:8px;" title="Cancel">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
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
</body>
</html>