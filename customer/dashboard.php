<?php
// customer/dashboard.php
require_once '../includes/config.php';
requireLogin('customer');

$db = getDB();
$uid = $_SESSION['user_id'];

// Customer stats
$myRentals    = $db->prepare("SELECT COUNT(*) FROM rentals WHERE user_id=?"); $myRentals->execute([$uid]); $myRentals = $myRentals->fetchColumn();
$activeRent   = $db->prepare("SELECT COUNT(*) FROM rentals WHERE user_id=? AND rental_status IN ('Active','Approved')"); $activeRent->execute([$uid]); $activeRent = $activeRent->fetchColumn();
$totalSpent   = $db->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN rentals r ON p.rental_id=r.id WHERE r.user_id=? AND p.payment_status='Completed'"); $totalSpent->execute([$uid]); $totalSpent = $totalSpent->fetchColumn();
$availVehicles= $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();

// My recent rentals
$stmt = $db->prepare("SELECT r.*, v.vehicle_name, v.brand, v.image, v.price_per_day FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$myRecentRentals = $stmt->fetchAll();

// My notifications
$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=? AND status='unread' ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$uid]);
$myNotifs = $notifs->fetchAll();

// Featured vehicles
$featuredVehicles = $db->query("SELECT v.*, vt.type_name FROM vehicles v LEFT JOIN vehicle_types vt ON v.type_id=vt.id WHERE v.status='Available' ORDER BY v.price_per_day DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Welcome Banner -->
    <div class="welcome-banner mb-4">
      <div class="welcome-orb"></div>
      <div class="welcome-orb welcome-orb-2"></div>
      <div class="position-relative z-2">
        <h3>Welcome back, <?= clean(explode(' ', $_SESSION['full_name'])[0]) ?>! 👋</h3>
        <p>Ready for your next adventure? <?= $availVehicles ?> vehicles are available right now.</p>
        <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-primary-glow">
          <i class="bi bi-search me-2"></i>Browse Fleet
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="cust-stat-card">
          <div class="cust-stat-icon" style="background:rgba(59,130,246,0.15);color:var(--accent-blue);"><i class="bi bi-calendar2-check-fill"></i></div>
          <div class="cust-stat-value counter" data-target="<?= $myRentals ?>"><?= $myRentals ?></div>
          <div class="cust-stat-label">Total Rentals</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="cust-stat-card">
          <div class="cust-stat-icon" style="background:rgba(16,185,129,0.15);color:var(--accent-green);"><i class="bi bi-car-front-fill"></i></div>
          <div class="cust-stat-value counter" data-target="<?= $activeRent ?>"><?= $activeRent ?></div>
          <div class="cust-stat-label">Active Rentals</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="cust-stat-card">
          <div class="cust-stat-icon" style="background:rgba(245,158,11,0.15);color:var(--accent-orange);"><i class="bi bi-cash-coin"></i></div>
          <div class="cust-stat-value" style="font-size:1rem;">PKR <?= number_format($totalSpent,0) ?></div>
          <div class="cust-stat-label">Total Spent</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="cust-stat-card">
          <div class="cust-stat-icon" style="background:rgba(139,92,246,0.15);color:var(--accent-purple);"><i class="bi bi-grid-1x2-fill"></i></div>
          <div class="cust-stat-value counter" data-target="<?= $availVehicles ?>"><?= $availVehicles ?></div>
          <div class="cust-stat-label">Available Now</div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Recent Rentals -->
      <div class="col-lg-8">
        <div class="data-card">
          <div class="data-card-header">
            <h5 class="data-card-title">My Recent Rentals</h5>
            <a href="<?= SITE_URL ?>/customer/my_rentals.php" class="btn btn-glass btn-sm">View All</a>
          </div>
          <div class="data-card-body">
            <?php if (empty($myRecentRentals)): ?>
              <div class="empty-state">
                <i class="bi bi-calendar2-plus"></i>
                <h5>No Rentals Yet</h5>
                <p>You haven't rented a vehicle yet.</p>
                <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-primary-glow btn-sm mt-2">Browse Vehicles</a>
              </div>
            <?php else: ?>
            <table class="df-table">
              <thead><tr><th>Vehicle</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
              <?php foreach ($myRecentRentals as $r): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <?php $img = '../assets/images/vehicles/'.$r['image'];
                    if ($r['image'] && $r['image'] !== 'default.jpg' && file_exists($img)): ?>
                      <img src="<?= $img ?>" class="vehicle-thumb" alt="">
                    <?php else: ?>
                      <div class="vehicle-thumb-placeholder"><i class="bi bi-car-front"></i></div>
                    <?php endif; ?>
                    <div>
                      <div style="font-size:13px;font-weight:600;"><?= clean($r['vehicle_name']) ?></div>
                      <div style="font-size:11px;color:var(--text-muted);"><?= clean($r['brand']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:12px;"><?= $r['start_date'] ?><br><span style="color:var(--text-muted);">to <?= $r['end_date'] ?></span></td>
                <td style="color:var(--accent-cyan);font-weight:600;"><?= formatCurrency($r['total_price']) ?></td>
                <td><span class="badge-<?= strtolower($r['rental_status']) ?>"><?= $r['rental_status'] ?></span></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar: Notifications + Quick Actions -->
      <div class="col-lg-4">
        <!-- Notifications -->
        <?php if (!empty($myNotifs)): ?>
        <div class="data-card mb-4">
          <div class="data-card-header">
            <h5 class="data-card-title">Notifications</h5>
            <span class="badge-pending"><?= count($myNotifs) ?> new</span>
          </div>
          <div class="data-card-body">
            <ul class="activity-list">
              <?php foreach ($myNotifs as $n): ?>
              <li class="activity-item">
                <div class="activity-icon"><i class="bi bi-bell-fill"></i></div>
                <div>
                  <div style="font-size:13px;font-weight:600;"><?= clean($n['title']) ?></div>
                  <div class="activity-text"><?= clean(substr($n['message'],0,60)) ?>...</div>
                  <div class="activity-time"><?= date('M d, h:i A', strtotime($n['created_at'])) ?></div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="data-card">
          <div class="data-card-header"><h5 class="data-card-title">Quick Actions</h5></div>
          <div class="data-card-body">
            <div class="d-flex flex-column gap-2">
              <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass text-start">
                <i class="bi bi-search me-2"></i>Browse Vehicles
              </a>
              <a href="<?= SITE_URL ?>/customer/my_rentals.php" class="btn btn-glass text-start">
                <i class="bi bi-calendar2-check me-2"></i>My Rentals
              </a>
              <a href="<?= SITE_URL ?>/customer/feedback.php" class="btn btn-glass text-start">
                <i class="bi bi-star me-2"></i>Leave Feedback
              </a>
              <a href="<?= SITE_URL ?>/customer/profile.php" class="btn btn-glass text-start">
                <i class="bi bi-person me-2"></i>My Profile
              </a>
              <a href="<?= SITE_URL ?>/logout.php" class="btn text-start" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15);color:#fca5a5;border-radius:10px;padding:10px 16px;">
                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Vehicles -->
    <?php if (!empty($featuredVehicles)): ?>
    <div class="mt-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Available Now</h5>
        <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass btn-sm">See All</a>
      </div>
      <div class="row g-3">
        <?php foreach ($featuredVehicles as $v): ?>
        <div class="col-md-4">
          <div class="vehicle-card glass-card">
            <div class="vehicle-img-wrap">
              <?php $imgPath = '../assets/images/vehicles/'.$v['image'];
              $imgUrl = ($v['image'] && $v['image'] !== 'default.jpg' && file_exists($imgPath)) ? $imgPath : '../assets/images/car-placeholder.svg'; ?>
              <img src="<?= $imgUrl ?>" alt="<?= clean($v['vehicle_name']) ?>" class="vehicle-img">
              <span class="vehicle-type-badge"><?= clean($v['type_name'] ?? 'Vehicle') ?></span>
              <span class="vehicle-status-badge status-available">Available</span>
            </div>
            <div class="vehicle-card-body">
              <h5><?= clean($v['vehicle_name']) ?></h5>
              <p class="vehicle-brand"><i class="bi bi-building me-1"></i><?= clean($v['brand']) ?></p>
              <div class="vehicle-card-footer d-flex justify-content-between align-items-center">
                <div>
                  <span class="price-amount"><?= formatCurrency($v['price_per_day']) ?></span>
                  <span class="price-unit">/day</span>
                </div>
                <a href="<?= SITE_URL ?>/customer/rent.php?id=<?= $v['id'] ?>" class="btn btn-primary-glow btn-sm">Book Now</a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>