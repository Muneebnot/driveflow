<?php
// admin/vehicles.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $vehicle = $db->prepare("SELECT image FROM vehicles WHERE id=?");
    $vehicle->execute([$id]);
    $veh = $vehicle->fetch();
    if ($veh && $veh['image'] !== 'default.jpg') {
        @unlink(UPLOAD_PATH . $veh['image']);
    }
    $db->prepare("DELETE FROM vehicles WHERE id=?")->execute([$id]);
    logActivity($_SESSION['user_id'], "Deleted vehicle ID: $id");
    setFlash('success', 'Vehicle deleted successfully.');
    redirect(SITE_URL . '/admin/vehicles.php');
}

// Filters
$search = trim($_GET['search'] ?? '');
$filterType   = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
if ($search) { $where .= " AND (v.vehicle_name LIKE ? OR v.brand LIKE ? OR v.vehicle_number LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($filterType)   { $where .= " AND v.type_id = ?"; $params[] = $filterType; }
if ($filterStatus) { $where .= " AND v.status = ?"; $params[] = $filterStatus; }

$stmt = $db->prepare("SELECT v.*, vt.type_name FROM vehicles v LEFT JOIN vehicle_types vt ON v.type_id=vt.id WHERE $where ORDER BY v.created_at DESC");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$vehicleTypes = $db->query("SELECT * FROM vehicle_types ORDER BY type_name")->fetchAll();

$pageTitle = 'Vehicles';
$breadcrumb = 'Fleet Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicles – DriveFlow Admin</title>
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

      <!-- Page Header -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h4 class="mb-1">Fleet Management</h4>
          <p style="color:var(--text-muted);font-size:13px;margin:0;"><?= count($vehicles) ?> vehicles in database</p>
        </div>
        <a href="<?= SITE_URL ?>/admin/add_vehicle.php" class="btn btn-primary-glow">
          <i class="bi bi-plus-circle-fill me-2"></i>Add Vehicle
        </a>
      </div>

      <!-- Filters -->
      <form method="GET" class="filter-bar mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search vehicle, brand, number..." value="<?= clean($search) ?>">
        <select name="type" class="form-select">
          <option value="">All Types</option>
          <?php foreach ($vehicleTypes as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filterType==$t['id'] ? 'selected' : '' ?>><?= clean($t['type_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="Available" <?= $filterStatus=='Available' ? 'selected' : '' ?>>Available</option>
          <option value="Rented" <?= $filterStatus=='Rented' ? 'selected' : '' ?>>Rented</option>
          <option value="Maintenance" <?= $filterStatus=='Maintenance' ? 'selected' : '' ?>>Maintenance</option>
        </select>
        <button type="submit" class="btn btn-glass"><i class="bi bi-funnel me-1"></i>Filter</button>
        <?php if ($search || $filterType || $filterStatus): ?>
          <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-glass"><i class="bi bi-x me-1"></i>Clear</a>
        <?php endif; ?>
      </form>

      <!-- Vehicles Table -->
      <div class="data-card">
        <div class="data-card-body">
          <?php if (empty($vehicles)): ?>
            <div class="empty-state">
              <i class="bi bi-car-front"></i>
              <h5>No Vehicles Found</h5>
              <p>Try adjusting your filters or <a href="<?= SITE_URL ?>/admin/add_vehicle.php" style="color:var(--accent-blue);">add a new vehicle</a>.</p>
            </div>
          <?php else: ?>
          <table class="df-table">
            <thead><tr>
              <th>Vehicle</th><th>Number</th><th>Type</th><th>Price/Day</th>
              <th>Fuel</th><th>Transmission</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($vehicles as $v): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <?php
                  $imgPath = '../assets/images/vehicles/' . $v['image'];
                  if ($v['image'] && $v['image'] !== 'default.jpg' && file_exists($imgPath)):
                  ?>
                    <img src="<?= $imgPath ?>" alt="" class="vehicle-thumb">
                  <?php else: ?>
                    <div class="vehicle-thumb-placeholder"><i class="bi bi-car-front"></i></div>
                  <?php endif; ?>
                  <div>
                    <div style="font-weight:600;font-size:13px;"><?= clean($v['vehicle_name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= clean($v['brand']) ?> · <?= $v['year'] ?></div>
                  </div>
                </div>
              </td>
              <td><code style="background:rgba(59,130,246,0.1);padding:2px 8px;border-radius:4px;font-size:12px;color:var(--accent-blue);"><?= clean($v['vehicle_number']) ?></code></td>
              <td><?= clean($v['type_name'] ?? '—') ?></td>
              <td style="color:var(--accent-cyan);font-weight:600;"><?= formatCurrency($v['price_per_day']) ?></td>
              <td><?= clean($v['fuel_type']) ?></td>
              <td><?= clean($v['transmission']) ?></td>
              <td>
                <span class="badge-<?= strtolower($v['status']) === 'available' ? 'active' : (strtolower($v['status']) === 'rented' ? 'pending' : 'cancelled') ?>"><?= $v['status'] ?></span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a href="<?= SITE_URL ?>/admin/edit_vehicle.php?id=<?= $v['id'] ?>" class="btn btn-glass btn-sm" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <button onclick="confirmDelete('<?= SITE_URL ?>/admin/vehicles.php?delete=<?= $v['id'] ?>', 'Delete <?= clean($v['vehicle_name']) ?>?')" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;border-radius:8px;" title="Delete">
                    <i class="bi bi-trash-fill"></i>
                  </button>
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