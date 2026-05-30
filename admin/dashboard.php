<?php
// admin/dashboard.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();

// ── KPI Stats ──
$totalVehicles   = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$availVehicles   = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();
$rentedVehicles  = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Rented'")->fetchColumn();
$maintVehicles   = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Maintenance'")->fetchColumn();
$totalCustomers  = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalRentals    = $db->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$pendingRentals  = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Pending'")->fetchColumn();
$activeRentals   = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Active'")->fetchColumn();
$totalRevenue    = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed'")->fetchColumn();
$monthRevenue    = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed' AND MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())")->fetchColumn();
$avgRating       = $db->query("SELECT COALESCE(ROUND(AVG(rating),1),0) FROM feedback")->fetchColumn();

// ── Monthly Revenue (last 6 months) ──
$monthlyRevenue = $db->query("
    SELECT DATE_FORMAT(payment_date,'%b') as month_label,
           MONTH(payment_date) as month_num,
           YEAR(payment_date) as yr,
           COALESCE(SUM(amount),0) as revenue
    FROM payments
    WHERE payment_status='Completed'
      AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(payment_date), MONTH(payment_date)
    ORDER BY yr, month_num
")->fetchAll();

// ── Monthly Rentals (last 6 months) ──
$monthlyRentals = $db->query("
    SELECT DATE_FORMAT(created_at,'%b') as month_label,
           COUNT(*) as count
    FROM rentals
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
")->fetchAll();

// ── Vehicle Types Distribution ──
$vehicleTypeStats = $db->query("
    SELECT vt.type_name, COUNT(v.id) as count
    FROM vehicle_types vt
    LEFT JOIN vehicles v ON v.type_id = vt.id
    GROUP BY vt.id, vt.type_name
    ORDER BY count DESC
")->fetchAll();

// ── Recent Rentals ──
$recentRentals = $db->query("
    SELECT r.*, u.full_name, v.vehicle_name
    FROM rentals r
    JOIN users u ON r.user_id = u.id
    JOIN vehicles v ON r.vehicle_id = v.id
    ORDER BY r.created_at DESC
    LIMIT 8
")->fetchAll();

// ── Recent Activity ──
$recentActivity = $db->query("
    SELECT al.*, u.full_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();

// JSON for charts
$revenueLabels  = json_encode(array_column($monthlyRevenue, 'month_label'));
$revenueData    = json_encode(array_column($monthlyRevenue, 'revenue'));
$rentalLabels   = json_encode(array_column($monthlyRentals, 'month_label'));
$rentalData     = json_encode(array_column($monthlyRentals, 'count'));
$typeLabels     = json_encode(array_column($vehicleTypeStats, 'type_name'));
$typeCounts     = json_encode(array_column($vehicleTypeStats, 'count'));

$pageTitle = 'Dashboard';
$breadcrumb = 'Overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – DriveFlow Admin</title>
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
      <!-- Flash -->
      <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div>
      <?php endif; ?>

      <!-- ── ROW 1: KPI Cards ── -->
      <div class="row g-3 stat-cards-row">
        <div class="col-6 col-lg-3">
          <div class="stat-card blue">
            <div class="stat-icon blue"><i class="bi bi-car-front-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $totalVehicles ?>"><?= $totalVehicles ?></span>
              <span class="stat-label">Total Vehicles</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card green">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $availVehicles ?>"><?= $availVehicles ?></span>
              <span class="stat-label">Available</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card orange">
            <div class="stat-icon orange"><i class="bi bi-calendar2-check-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $activeRentals ?>"><?= $activeRentals ?></span>
              <span class="stat-label">Active Rentals</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $totalCustomers ?>"><?= $totalCustomers ?></span>
              <span class="stat-label">Customers</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ROW 2: Revenue + Pending cards ── -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="stat-card cyan">
            <div class="stat-icon cyan"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-info">
              <span class="stat-value" style="font-size:1.3rem;">PKR <?= number_format($totalRevenue, 0) ?></span>
              <span class="stat-label">Total Revenue</span>
              <span class="stat-change up"><i class="bi bi-arrow-up-right"></i>This month: PKR <?= number_format($monthRevenue, 0) ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card orange">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $pendingRentals ?>"><?= $pendingRentals ?></span>
              <span class="stat-label">Pending Rentals</span>
              <a href="<?= SITE_URL ?>/admin/rentals.php?status=Pending" class="stat-change" style="color:var(--accent-orange);text-decoration:none;font-size:11px;">Review now →</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card pink">
            <div class="stat-icon pink"><i class="bi bi-star-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value"><?= $avgRating ?> <span style="font-size:1rem;">★</span></span>
              <span class="stat-label">Avg. Rating</span>
              <span class="stat-change up"><i class="bi bi-chat-square-dots-fill"></i><?= $db->query("SELECT COUNT(*) FROM feedback")->fetchColumn() ?> reviews total</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ROW 3: Charts ── -->
      <div class="row g-4 mb-4">
        <!-- Revenue Chart -->
        <div class="col-lg-8">
          <div class="chart-card">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Revenue Overview</div>
                <div class="chart-card-subtitle">Last 6 months performance</div>
              </div>
              <span class="chart-badge">PKR <?= number_format($totalRevenue/1000, 1) ?>K Total</span>
            </div>
            <canvas id="revenueChart" height="100"></canvas>
          </div>
        </div>
        <!-- Fleet Status Donut -->
        <div class="col-lg-4">
          <div class="chart-card h-100">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Fleet Status</div>
                <div class="chart-card-subtitle">Vehicle availability</div>
              </div>
            </div>
            <div class="d-flex justify-content-center mb-3">
              <canvas id="fleetChart" width="180" height="180"></canvas>
            </div>
            <div class="fleet-legend">
              <div class="fleet-legend-item">
                <div class="fleet-dot" style="background:#10b981;"></div>
                <span style="font-size:13px;flex:1;">Available</span>
                <strong><?= $availVehicles ?></strong>
              </div>
              <div class="fleet-legend-item">
                <div class="fleet-dot" style="background:#f59e0b;"></div>
                <span style="font-size:13px;flex:1;">Rented</span>
                <strong><?= $rentedVehicles ?></strong>
              </div>
              <div class="fleet-legend-item">
                <div class="fleet-dot" style="background:#ef4444;"></div>
                <span style="font-size:13px;flex:1;">Maintenance</span>
                <strong><?= $maintVehicles ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ROW 4: Rentals Chart + Vehicle Types ── -->
      <div class="row g-4 mb-4">
        <!-- Rentals per month -->
        <div class="col-lg-6">
          <div class="chart-card">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Monthly Rentals</div>
                <div class="chart-card-subtitle">Booking trends last 6 months</div>
              </div>
            </div>
            <canvas id="rentalsChart" height="130"></canvas>
          </div>
        </div>
        <!-- Vehicle Types Table -->
        <div class="col-lg-6">
          <div class="data-card">
            <div class="data-card-header">
              <h5 class="data-card-title">Fleet by Type</h5>
              <a href="<?= SITE_URL ?>/admin/vehicle_types.php" class="btn btn-glass btn-sm">Manage Types</a>
            </div>
            <div class="data-card-body">
              <?php
              $total = array_sum(array_column($vehicleTypeStats, 'count'));
              $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4','#ec4899','#14b8a6'];
              foreach ($vehicleTypeStats as $i => $vt):
                $pct = $total > 0 ? round(($vt['count']/$total)*100) : 0;
                $col = $colors[$i % count($colors)];
              ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span style="font-size:13px;"><?= clean($vt['type_name']) ?></span>
                  <span style="font-size:12px;color:var(--text-muted);"><?= $vt['count'] ?> vehicles (<?= $pct ?>%)</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ROW 5: Recent Rentals + Activity ── -->
      <div class="row g-4">
        <!-- Recent Rentals -->
        <div class="col-lg-8">
          <div class="data-card">
            <div class="data-card-header">
              <h5 class="data-card-title">Recent Rentals</h5>
              <a href="<?= SITE_URL ?>/admin/rentals.php" class="btn btn-glass btn-sm">View All</a>
            </div>
            <div class="data-card-body">
              <?php if (empty($recentRentals)): ?>
                <div class="empty-state"><i class="bi bi-calendar2-x"></i><h5>No rentals yet</h5></div>
              <?php else: ?>
              <table class="df-table">
                <thead><tr>
                  <th>#</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Amount</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php foreach ($recentRentals as $r): ?>
                <tr>
                  <td style="color:var(--text-muted);">#<?= $r['id'] ?></td>
                  <td><?= clean($r['full_name']) ?></td>
                  <td><?= clean($r['vehicle_name']) ?></td>
                  <td style="font-size:12px;"><?= $r['start_date'] ?><br><span style="color:var(--text-muted);">to <?= $r['end_date'] ?></span></td>
                  <td style="color:var(--accent-cyan);font-weight:600;"><?= formatCurrency($r['total_price']) ?></td>
                  <td>
                    <span class="badge-<?= strtolower($r['rental_status']) ?>"><?= $r['rental_status'] ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <!-- Activity Timeline -->
        <div class="col-lg-4">
          <div class="data-card">
            <div class="data-card-header">
              <h5 class="data-card-title">Recent Activity</h5>
              <a href="<?= SITE_URL ?>/admin/activity_logs.php" class="btn btn-glass btn-sm">All Logs</a>
            </div>
            <div class="data-card-body">
              <ul class="activity-list">
                <?php foreach ($recentActivity as $act): ?>
                <li class="activity-item">
                  <div class="activity-icon"><i class="bi bi-activity"></i></div>
                  <div>
                    <div class="activity-text"><?= clean($act['activity']) ?></div>
                    <div class="activity-time"><i class="bi bi-clock me-1"></i><?= date('M d, h:i A', strtotime($act['created_at'])) ?></div>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div><!-- end content-area -->
  </div><!-- end main-content -->
</div><!-- end admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'DM Sans', sans-serif";

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueGrad = revenueCtx.createLinearGradient(0, 0, 0, 280);
revenueGrad.addColorStop(0, 'rgba(59,130,246,0.4)');
revenueGrad.addColorStop(1, 'rgba(59,130,246,0.01)');

new Chart(revenueCtx, {
  type: 'line',
  data: {
    labels: <?= $revenueLabels ?>,
    datasets: [{
      label: 'Revenue (PKR)',
      data: <?= $revenueData ?>,
      borderColor: '#3b82f6',
      backgroundColor: revenueGrad,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#3b82f6',
      pointRadius: 5,
      pointHoverRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: {
      callbacks: { label: ctx => ' PKR ' + ctx.raw.toLocaleString() }
    }},
    scales: {
      y: { ticks: { callback: v => 'PKR ' + (v/1000).toFixed(0) + 'K' } }
    }
  }
});

// Fleet Donut
new Chart(document.getElementById('fleetChart'), {
  type: 'doughnut',
  data: {
    labels: ['Available', 'Rented', 'Maintenance'],
    datasets: [{
      data: [<?= $availVehicles ?>, <?= $rentedVehicles ?>, <?= $maintVehicles ?>],
      backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
      borderWidth: 0,
      hoverOffset: 8,
    }]
  },
  options: {
    cutout: '72%',
    plugins: { legend: { display: false } }
  }
});

// Rentals Bar Chart
new Chart(document.getElementById('rentalsChart'), {
  type: 'bar',
  data: {
    labels: <?= $rentalLabels ?>,
    datasets: [{
      label: 'Rentals',
      data: <?= $rentalData ?>,
      backgroundColor: 'rgba(139,92,246,0.7)',
      borderColor: '#8b5cf6',
      borderWidth: 1,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>
</body>
</html>