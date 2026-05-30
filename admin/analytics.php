<?php
// admin/analytics.php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

// Revenue by month (last 12)
$revenueMonthly = $db->query("
  SELECT DATE_FORMAT(payment_date,'%b %Y') as label,
         MONTH(payment_date) as m, YEAR(payment_date) as y,
         COALESCE(SUM(amount),0) as revenue, COUNT(*) as transactions
  FROM payments WHERE payment_status='Completed'
    AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY YEAR(payment_date), MONTH(payment_date)
  ORDER BY y, m
")->fetchAll();

// Rentals by month (last 12)
$rentalsMonthly = $db->query("
  SELECT DATE_FORMAT(created_at,'%b %Y') as label,
         COUNT(*) as total,
         SUM(rental_status='Completed') as completed,
         SUM(rental_status='Cancelled') as cancelled
  FROM rentals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY YEAR(created_at), MONTH(created_at)
  ORDER BY YEAR(created_at), MONTH(created_at)
")->fetchAll();

// Top vehicles by rentals
$topVehicles = $db->query("
  SELECT v.vehicle_name, v.brand, COUNT(r.id) as rental_count,
         COALESCE(SUM(r.total_price),0) as revenue
  FROM vehicles v LEFT JOIN rentals r ON r.vehicle_id=v.id
  GROUP BY v.id ORDER BY rental_count DESC LIMIT 5
")->fetchAll();

// Revenue by payment method
$paymentMethods = $db->query("
  SELECT payment_method, COUNT(*) as count, SUM(amount) as total
  FROM payments WHERE payment_status='Completed'
  GROUP BY payment_method
")->fetchAll();

// KPIs
$totalRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed'")->fetchColumn();
$monthRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed' AND MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())")->fetchColumn();
$totalRentals  = $db->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$completedRent = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Completed'")->fetchColumn();
$cancelledRent = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Cancelled'")->fetchColumn();
$avgRentalVal  = $db->query("SELECT COALESCE(AVG(total_price),0) FROM rentals WHERE rental_status='Completed'")->fetchColumn();
$totalCustomers= $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$newCustomers  = $db->query("SELECT COUNT(*) FROM users WHERE role='customer' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// JSON
$revLabels  = json_encode(array_column($revenueMonthly, 'label'));
$revData    = json_encode(array_column($revenueMonthly, 'revenue'));
$renLabels  = json_encode(array_column($rentalsMonthly, 'label'));
$renTotal   = json_encode(array_column($rentalsMonthly, 'total'));
$renComp    = json_encode(array_column($rentalsMonthly, 'completed'));
$methodLabels = json_encode(array_column($paymentMethods, 'payment_method'));
$methodTotals = json_encode(array_column($paymentMethods, 'total'));

$pageTitle = 'Analytics'; $breadcrumb = 'Reports & Analytics';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics – DriveFlow Admin</title>
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

      <div class="mb-4"><h4 class="mb-1">Analytics & Reports</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">Real-time business performance data</p></div>

      <!-- KPI Row -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card cyan">
            <div class="stat-icon cyan"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-info">
              <span class="stat-value" style="font-size:1.2rem;">PKR <?= number_format($totalRevenue/1000,1) ?>K</span>
              <span class="stat-label">Total Revenue</span>
              <span class="stat-change up"><i class="bi bi-arrow-up-right"></i>This month: PKR <?= number_format($monthRevenue,0) ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-calendar2-check-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $totalRentals ?>"><?= $totalRentals ?></span>
              <span class="stat-label">Total Rentals</span>
              <span class="stat-change up"><i class="bi bi-check2"></i><?= $completedRent ?> completed</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card green">
            <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-info">
              <span class="stat-value" style="font-size:1.2rem;">PKR <?= number_format($avgRentalVal,0) ?></span>
              <span class="stat-label">Avg Rental Value</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card pink">
            <div class="stat-icon pink"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
              <span class="stat-value counter" data-target="<?= $totalCustomers ?>"><?= $totalCustomers ?></span>
              <span class="stat-label">Total Customers</span>
              <span class="stat-change up"><i class="bi bi-arrow-up-right"></i><?= $newCustomers ?> new this month</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue Chart (12 months) -->
      <div class="row g-4 mb-4">
        <div class="col-12">
          <div class="chart-card">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Annual Revenue Trend</div>
                <div class="chart-card-subtitle">Monthly revenue for the last 12 months</div>
              </div>
              <span class="chart-badge">Total: PKR <?= number_format($totalRevenue,0) ?></span>
            </div>
            <canvas id="annualRevenueChart" height="80"></canvas>
          </div>
        </div>
      </div>

      <!-- Rentals + Payment Methods -->
      <div class="row g-4 mb-4">
        <div class="col-lg-8">
          <div class="chart-card">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Rental Performance</div>
                <div class="chart-card-subtitle">Total vs Completed rentals per month</div>
              </div>
            </div>
            <canvas id="rentalPerfChart" height="120"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="chart-card h-100">
            <div class="chart-card-header">
              <div>
                <div class="chart-card-title">Payment Methods</div>
                <div class="chart-card-subtitle">Revenue by payment type</div>
              </div>
            </div>
            <div class="d-flex justify-content-center mb-3">
              <canvas id="payMethodChart" width="200" height="200"></canvas>
            </div>
            <div>
              <?php
              $pmColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6'];
              foreach ($paymentMethods as $i => $pm): ?>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:10px;height:10px;border-radius:50%;background:<?= $pmColors[$i%4] ?>;"></div>
                  <span style="font-size:12px;"><?= $pm['payment_method'] ?></span>
                </div>
                <span style="font-size:12px;color:var(--accent-cyan);font-weight:600;">PKR <?= number_format($pm['total'],0) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Vehicles + Completion Rate -->
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="data-card">
            <div class="data-card-header"><h5 class="data-card-title">Top Performing Vehicles</h5></div>
            <div class="data-card-body">
              <?php if (empty($topVehicles)): ?>
                <div class="empty-state"><i class="bi bi-car-front"></i><h5>No data yet</h5></div>
              <?php else: ?>
              <?php $maxRentals = max(array_column($topVehicles,'rental_count')) ?: 1; ?>
              <?php foreach ($topVehicles as $i => $tv): ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div>
                    <span style="font-size:13px;font-weight:600;"><?= clean($tv['vehicle_name']) ?></span>
                    <span style="font-size:11px;color:var(--text-muted);margin-left:8px;"><?= clean($tv['brand']) ?></span>
                  </div>
                  <div class="text-end">
                    <span style="font-size:12px;color:var(--accent-blue);"><?= $tv['rental_count'] ?> rentals</span>
                    <span style="font-size:11px;color:var(--accent-cyan);display:block;">PKR <?= number_format($tv['revenue'],0) ?></span>
                  </div>
                </div>
                <?php $pct = round(($tv['rental_count']/$maxRentals)*100); ?>
                <div class="progress">
                  <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4'][$i] ?>;"></div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="data-card">
            <div class="data-card-header"><h5 class="data-card-title">Rental Completion Rate</h5></div>
            <div class="data-card-body">
              <?php
              $rates = [
                ['label'=>'Completed','count'=>$completedRent,'color'=>'#10b981'],
                ['label'=>'Cancelled','count'=>$cancelledRent,'color'=>'#ef4444'],
                ['label'=>'Active/Other','count'=>$totalRentals-$completedRent-$cancelledRent,'color'=>'#f59e0b'],
              ];
              foreach ($rates as $rt):
                $pct = $totalRentals > 0 ? round(($rt['count']/$totalRentals)*100) : 0;
              ?>
              <div class="mb-4">
                <div class="d-flex justify-content-between mb-2">
                  <span style="font-size:13px;"><?= $rt['label'] ?></span>
                  <span style="font-size:13px;font-weight:700;color:<?= $rt['color'] ?>;"><?= $pct ?>%</span>
                </div>
                <div class="progress" style="height:10px;">
                  <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $rt['color'] ?>;border-radius:5px;"></div>
                </div>
                <small style="color:var(--text-muted);font-size:11px;"><?= $rt['count'] ?> rentals</small>
              </div>
              <?php endforeach; ?>

              <div style="border-top:1px solid var(--dark-border);padding-top:16px;margin-top:8px;">
                <div class="d-flex justify-content-between">
                  <span style="font-size:13px;color:var(--text-muted);">Completion Rate</span>
                  <span style="font-size:1.2rem;font-weight:800;color:var(--accent-green);">
                    <?= $totalRentals > 0 ? round(($completedRent/$totalRentals)*100) : 0 ?>%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'DM Sans', sans-serif";

// Annual Revenue
const arCtx = document.getElementById('annualRevenueChart').getContext('2d');
const arGrad = arCtx.createLinearGradient(0,0,0,250);
arGrad.addColorStop(0,'rgba(6,182,212,0.35)');
arGrad.addColorStop(1,'rgba(6,182,212,0.01)');
new Chart(arCtx, {
  type: 'line',
  data: {
    labels: <?= $revLabels ?>,
    datasets: [{
      label: 'Revenue',
      data: <?= $revData ?>,
      borderColor: '#06b6d4',
      backgroundColor: arGrad,
      fill: true, tension: 0.4,
      pointBackgroundColor: '#06b6d4', pointRadius: 5, pointHoverRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' PKR '+ctx.raw.toLocaleString() } } },
    scales: { y: { ticks: { callback: v => 'PKR '+(v/1000).toFixed(0)+'K' } } }
  }
});

// Rental Performance
new Chart(document.getElementById('rentalPerfChart'), {
  type: 'bar',
  data: {
    labels: <?= $renLabels ?>,
    datasets: [
      { label: 'Total Rentals', data: <?= $renTotal ?>, backgroundColor: 'rgba(139,92,246,0.6)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 6 },
      { label: 'Completed', data: <?= $renComp ?>, backgroundColor: 'rgba(16,185,129,0.6)', borderColor: '#10b981', borderWidth: 1, borderRadius: 6 },
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});

// Payment Methods
new Chart(document.getElementById('payMethodChart'), {
  type: 'pie',
  data: {
    labels: <?= $methodLabels ?>,
    datasets: [{
      data: <?= $methodTotals ?>,
      backgroundColor: ['#3b82f6','#10b981','#f59e0b','#8b5cf6'],
      borderWidth: 0, hoverOffset: 8,
    }]
  },
  options: {
    plugins: { legend: { display: false } }
  }
});
</script>
</body></html>