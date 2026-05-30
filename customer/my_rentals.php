<?php
// customer/my_rentals.php
require_once '../includes/config.php';
requireLogin('customer');

$db  = getDB();
$uid = $_SESSION['user_id'];

// Cancel rental
if (isset($_GET['cancel'])) {
    $rid = (int)$_GET['cancel'];
    $check = $db->prepare("SELECT * FROM rentals WHERE id=? AND user_id=?");
    $check->execute([$rid, $uid]);
    $rental = $check->fetch();
    if ($rental && in_array($rental['rental_status'], ['Pending'])) {
        $db->prepare("UPDATE rentals SET rental_status='Cancelled' WHERE id=?")->execute([$rid]);
        logActivity($uid, "Cancelled rental #$rid");
        setFlash('warning', 'Rental cancelled successfully.');
    } else {
        setFlash('danger', 'Cannot cancel this rental. Only pending rentals can be cancelled.');
    }
    redirect(SITE_URL.'/customer/my_rentals.php');
}

$filterStatus = $_GET['status'] ?? '';
$where = "r.user_id = ?"; $params = [$uid];
if ($filterStatus) { $where .= " AND r.rental_status=?"; $params[] = $filterStatus; }

$stmt = $db->prepare("SELECT r.*, v.vehicle_name, v.brand, v.image, v.vehicle_number, vt.type_name, p.payment_status, p.payment_method FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id LEFT JOIN vehicle_types vt ON v.type_id=vt.id LEFT JOIN payments p ON p.rental_id=r.id WHERE $where ORDER BY r.created_at DESC");
$stmt->execute($params);
$rentals = $stmt->fetchAll();

// Stats
$totals = $db->prepare("SELECT rental_status, COUNT(*) as cnt FROM rentals WHERE user_id=? GROUP BY rental_status"); $totals->execute([$uid]); $statusCounts = [];
foreach ($totals->fetchAll() as $row) $statusCounts[$row['rental_status']] = $row['cnt'];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Rentals – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head><body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div><h4 class="mb-1">My Rentals</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">All your rental history</p></div>
      <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-primary-glow btn-sm"><i class="bi bi-plus me-1"></i>New Booking</a>
    </div>

    <!-- Status Filter Chips -->
    <div class="d-flex gap-2 flex-wrap mb-4">
      <a href="?status=" class="filter-chip <?= !$filterStatus ? 'active' : '' ?>">
        <i class="bi bi-grid"></i>All <span style="margin-left:4px;font-size:11px;">(<?= array_sum($statusCounts) ?>)</span>
      </a>
      <?php
      $chips = ['Pending'=>'clock','Approved'=>'check2','Active'=>'car-front','Completed'=>'flag','Cancelled'=>'x-circle'];
      foreach ($chips as $s => $icon): $count = $statusCounts[$s] ?? 0; ?>
      <a href="?status=<?= $s ?>" class="filter-chip <?= $filterStatus===$s ? 'active' : '' ?>">
        <i class="bi bi-<?= $icon ?>"></i><?= $s ?> <span style="margin-left:4px;font-size:11px;">(<?= $count ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($rentals)): ?>
      <div class="empty-state glass-card" style="padding:60px 20px;">
        <i class="bi bi-calendar2-x"></i>
        <h5>No Rentals Found</h5>
        <p><?= $filterStatus ? "No $filterStatus rentals." : "You haven't made any bookings yet." ?></p>
        <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-primary-glow mt-3"><i class="bi bi-search me-2"></i>Browse Vehicles</a>
      </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($rentals as $r):
        $days = max(1, (int)((strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400));
        $imgPath = '../assets/images/vehicles/'.$r['image'];
        $imgUrl  = ($r['image'] && $r['image'] !== 'default.jpg' && file_exists($imgPath)) ? $imgPath : '../assets/images/car-placeholder.svg';
      ?>
      <div class="col-12">
        <div class="glass-card" style="padding:0;overflow:hidden;">
          <div class="row g-0 align-items-center">
            <!-- Vehicle Image -->
            <div class="col-md-3 col-lg-2">
              <img src="<?= $imgUrl ?>" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:var(--radius) 0 0 var(--radius);">
            </div>
            <!-- Details -->
            <div class="col-md-9 col-lg-10">
              <div class="p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="mb-0" style="font-size:1rem;"><?= clean($r['vehicle_name']) ?></h5>
                    <span class="badge-<?= strtolower($r['rental_status']) ?>"><?= $r['rental_status'] ?></span>
                  </div>
                  <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">
                    <?= clean($r['brand']) ?> · <?= clean($r['type_name'] ?? '') ?> · <?= clean($r['vehicle_number']) ?>
                  </p>
                  <div class="d-flex flex-wrap gap-3" style="font-size:12px;color:var(--text-secondary);">
                    <span><i class="bi bi-calendar3 me-1"></i><?= $r['start_date'] ?> → <?= $r['end_date'] ?></span>
                    <span><i class="bi bi-clock me-1"></i><?= $days ?> day<?= $days > 1 ? 's' : '' ?></span>
                    <span><i class="bi bi-credit-card me-1"></i><?= $r['payment_method'] ?? 'Cash' ?> · <span class="<?= $r['payment_status']==='Completed' ? 'text-success' : '' ?>"><?= $r['payment_status'] ?? 'Pending' ?></span></span>
                  </div>
                </div>
                <div class="text-end">
                  <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:800;color:var(--accent-blue);"><?= formatCurrency($r['total_price']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;">Rental #<?= $r['id'] ?></div>
                  <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <?php if ($r['rental_status'] === 'Pending'): ?>
                      <button onclick="confirmDelete('<?= SITE_URL ?>/customer/my_rentals.php?cancel=<?= $r['id'] ?>', 'Cancel this rental request?')" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;border-radius:8px;font-size:12px;padding:5px 12px;">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                      </button>
                    <?php endif; ?>
                    <?php if ($r['rental_status'] === 'Completed'): ?>
                      <a href="<?= SITE_URL ?>/customer/feedback.php?vehicle_id=<?= $r['vehicle_id'] ?>" class="btn btn-sm" style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);color:#fcd34d;border-radius:8px;font-size:12px;padding:5px 12px;">
                        <i class="bi bi-star me-1"></i>Review
                      </a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass btn-sm" style="font-size:12px;padding:5px 12px;">
                      <i class="bi bi-arrow-repeat me-1"></i>Book Again
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>