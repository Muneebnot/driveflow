<?php
// customer/browse.php
require_once '../includes/config.php';
requireLogin('customer');

$db = getDB();

$search      = trim($_GET['search'] ?? '');
$filterType  = $_GET['type'] ?? '';
$filterPrice = $_GET['price'] ?? '';
$sort        = $_GET['sort'] ?? 'price_asc';

$where  = "v.status = 'Available'";
$params = [];

if ($search) {
    $where .= " AND (v.vehicle_name LIKE ? OR v.brand LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterType) {
    $where .= " AND v.type_id = ?";
    $params[] = $filterType;
}
if ($filterPrice) {
    list($min, $max) = explode('-', $filterPrice);
    $where .= " AND v.price_per_day BETWEEN ? AND ?";
    $params[] = $min; $params[] = $max;
}

$orderMap = [
    'price_asc'  => 'v.price_per_day ASC',
    'price_desc' => 'v.price_per_day DESC',
    'name_asc'   => 'v.vehicle_name ASC',
    'newest'     => 'v.created_at DESC',
];
$orderBy = $orderMap[$sort] ?? 'v.price_per_day ASC';

$stmt = $db->prepare("SELECT v.*, vt.type_name FROM vehicles v LEFT JOIN vehicle_types vt ON v.type_id=vt.id WHERE $where ORDER BY $orderBy");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$vehicleTypes = $db->query("SELECT vt.*, COUNT(v.id) as count FROM vehicle_types vt LEFT JOIN vehicles v ON v.type_id=vt.id AND v.status='Available' GROUP BY vt.id ORDER BY vt.type_name")->fetchAll();
$totalAvail   = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Vehicles – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head><body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">

    <!-- Browse Header -->
    <div class="browse-hero">
      <h4><i class="bi bi-search me-2" style="color:var(--accent-blue);"></i>Browse Our Fleet</h4>
      <p style="color:var(--text-muted);font-size:14px;margin-bottom:16px;"><?= $totalAvail ?> vehicles available right now</p>

      <!-- Search Bar -->
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="hidden" name="type"  value="<?= clean($filterType) ?>">
        <input type="hidden" name="price" value="<?= clean($filterPrice) ?>">
        <input type="hidden" name="sort"  value="<?= clean($sort) ?>">
        <div class="flex-grow-1" style="max-width:400px;">
          <div class="position-relative">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" style="padding-left:36px;" placeholder="Search vehicle name or brand..." value="<?= clean($search) ?>">
          </div>
        </div>
        <select name="sort" class="form-select" style="max-width:180px;" onchange="this.form.submit()">
          <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
          <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Name: A–Z</option>
          <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
        </select>
        <button type="submit" class="btn btn-primary-glow"><i class="bi bi-search me-1"></i>Search</button>
        <?php if ($search || $filterType || $filterPrice): ?>
          <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass"><i class="bi bi-x me-1"></i>Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="row g-4">
      <!-- Sidebar Filters -->
      <div class="col-lg-3">
        <div class="data-card mb-3">
          <div class="data-card-header"><h6 class="data-card-title mb-0">Vehicle Type</h6></div>
          <div class="data-card-body">
            <div class="d-flex flex-column gap-1">
              <a href="?search=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="filter-chip <?= !$filterType ? 'active' : '' ?>">
                <i class="bi bi-grid"></i>All Types <span style="margin-left:auto;font-size:11px;"><?= $totalAvail ?></span>
              </a>
              <?php foreach ($vehicleTypes as $t): ?>
              <a href="?search=<?= urlencode($search) ?>&type=<?= $t['id'] ?>&sort=<?= $sort ?>" class="filter-chip <?= $filterType==$t['id'] ? 'active' : '' ?>">
                <i class="bi bi-tag"></i><?= clean($t['type_name']) ?> <span style="margin-left:auto;font-size:11px;"><?= $t['count'] ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="data-card">
          <div class="data-card-header"><h6 class="data-card-title mb-0">Price Range (PKR/day)</h6></div>
          <div class="data-card-body">
            <div class="d-flex flex-column gap-1">
              <?php
              $ranges = [
                ''          => ['All Prices', ''],
                '0-3000'    => ['Up to PKR 3,000', 'bi-currency-exchange'],
                '3000-6000' => ['PKR 3K – 6K', 'bi-currency-exchange'],
                '6000-10000'=> ['PKR 6K – 10K', 'bi-currency-exchange'],
                '10000-100000'=> ['Above PKR 10K', 'bi-gem'],
              ];
              foreach ($ranges as $val => $info): ?>
              <a href="?search=<?= urlencode($search) ?>&type=<?= $filterType ?>&price=<?= $val ?>&sort=<?= $sort ?>" class="filter-chip <?= $filterPrice===$val ? 'active' : '' ?>">
                <i class="bi <?= $info[1] ?: 'bi-tag' ?>"></i><?= $info[0] ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Vehicle Grid -->
      <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span style="font-size:13px;color:var(--text-muted);"><?= count($vehicles) ?> vehicle<?= count($vehicles) !== 1 ? 's' : '' ?> found</span>
        </div>

        <?php if (empty($vehicles)): ?>
          <div class="empty-state glass-card" style="padding:60px 20px;">
            <i class="bi bi-car-front"></i>
            <h5>No Vehicles Found</h5>
            <p>Try adjusting your search or filters.</p>
            <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass mt-2">Clear Filters</a>
          </div>
        <?php else: ?>
        <div class="row g-3">
          <?php foreach ($vehicles as $v): ?>
          <div class="col-md-6 col-xl-4">
            <div class="vehicle-card glass-card">
              <div class="vehicle-img-wrap">
                <?php
                $imgPath = '../assets/images/vehicles/' . $v['image'];
                $imgUrl  = ($v['image'] && $v['image'] !== 'default.jpg' && file_exists($imgPath)) ? $imgPath : '../assets/images/car-placeholder.svg';
                ?>
                <img src="<?= $imgUrl ?>" alt="<?= clean($v['vehicle_name']) ?>" class="vehicle-img">
                <span class="vehicle-type-badge"><?= clean($v['type_name'] ?? 'Vehicle') ?></span>
                <span class="vehicle-status-badge status-available">Available</span>
              </div>
              <div class="vehicle-card-body">
                <h5><?= clean($v['vehicle_name']) ?></h5>
                <p class="vehicle-brand"><i class="bi bi-building me-1"></i><?= clean($v['brand']) ?> · <?= $v['year'] ?></p>
                <div class="vehicle-specs d-flex gap-3 mb-2">
                  <span><i class="bi bi-people-fill me-1"></i><?= $v['seats'] ?></span>
                  <span><i class="bi bi-fuel-pump me-1"></i><?= $v['fuel_type'] ?></span>
                  <span><i class="bi bi-gear me-1"></i><?= $v['transmission'] ?></span>
                </div>
                <?php if ($v['description']): ?>
                  <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;"><?= clean(substr($v['description'], 0, 70)) ?>...</p>
                <?php endif; ?>
                <div class="vehicle-card-footer d-flex justify-content-between align-items-center">
                  <div>
                    <span class="price-amount"><?= formatCurrency($v['price_per_day']) ?></span>
                    <span class="price-unit">/day</span>
                  </div>
                  <div class="d-flex gap-2">
                    <a href="<?= SITE_URL ?>/customer/vehicle_detail.php?id=<?= $v['id'] ?>" class="btn btn-glass btn-sm" title="Details">
                      <i class="bi bi-eye-fill"></i>
                    </a>
                    <a href="<?= SITE_URL ?>/customer/rent.php?id=<?= $v['id'] ?>" class="btn btn-primary-glow btn-sm">
                      Book
                    </a>
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

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>