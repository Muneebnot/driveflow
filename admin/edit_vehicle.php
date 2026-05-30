<?php
// admin/edit_vehicle.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger', 'Invalid vehicle.'); redirect(SITE_URL . '/admin/vehicles.php'); }

$vehicle = $db->prepare("SELECT * FROM vehicles WHERE id=?");
$vehicle->execute([$id]);
$v = $vehicle->fetch();
if (!$v) { setFlash('danger', 'Vehicle not found.'); redirect(SITE_URL . '/admin/vehicles.php'); }

$vehicleTypes = $db->query("SELECT * FROM vehicle_types ORDER BY type_name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_name   = trim($_POST['vehicle_name'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $type_id        = (int)($_POST['type_id'] ?? 0);
    $brand          = trim($_POST['brand'] ?? '');
    $price_per_day  = (float)($_POST['price_per_day'] ?? 0);
    $status         = $_POST['status'] ?? 'Available';
    $description    = trim($_POST['description'] ?? '');
    $seats          = (int)($_POST['seats'] ?? 5);
    $fuel_type      = trim($_POST['fuel_type'] ?? 'Petrol');
    $transmission   = trim($_POST['transmission'] ?? 'Manual');
    $year           = (int)($_POST['year'] ?? date('Y'));

    if (empty($vehicle_name))   $errors[] = 'Vehicle name is required.';
    if (empty($vehicle_number)) $errors[] = 'Vehicle number is required.';
    if ($price_per_day <= 0)    $errors[] = 'Price must be greater than 0.';

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM vehicles WHERE vehicle_number=? AND id!=?");
        $chk->execute([$vehicle_number, $id]);
        if ($chk->fetch()) $errors[] = 'Vehicle number already in use by another vehicle.';
    }

    // Image upload
    $imageName = $v['image'];
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image format.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image too large. Max 2MB.';
        } else {
            $uploadDir = '../assets/images/vehicles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if ($v['image'] && $v['image'] !== 'default.jpg') @unlink($uploadDir . $v['image']);
            $imageName = uniqid('veh_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE vehicles SET vehicle_name=?, vehicle_number=?, type_id=?, brand=?, price_per_day=?, status=?, image=?, description=?, seats=?, fuel_type=?, transmission=?, year=? WHERE id=?");
        $stmt->execute([$vehicle_name, $vehicle_number, $type_id ?: null, $brand, $price_per_day, $status, $imageName, $description, $seats, $fuel_type, $transmission, $year, $id]);
        logActivity($_SESSION['user_id'], "Updated vehicle: $vehicle_name (ID:$id)");
        setFlash('success', "Vehicle '$vehicle_name' updated successfully!");
        redirect(SITE_URL . '/admin/vehicles.php');
    }
    // Merge POST back for redisplay
    $v = array_merge($v, $_POST);
}

$pageTitle = 'Edit Vehicle';
$breadcrumb = 'Edit Vehicle';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Vehicle – DriveFlow Admin</title>
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
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h4 class="mb-1">Edit Vehicle</h4><p style="color:var(--text-muted);font-size:13px;margin:0;"><?= clean($v['vehicle_name']) ?></p></div>
        <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-glass"><i class="bi bi-arrow-left me-2"></i>Back</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
          <?php foreach ($errors as $e): ?><div><i class="bi bi-x-circle me-2"></i><?= clean($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="data-card mb-4">
              <div class="data-card-header"><h5 class="data-card-title">Vehicle Information</h5></div>
              <div class="data-card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Name *</label>
                    <input type="text" name="vehicle_name" class="form-control" value="<?= clean($v['vehicle_name']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" class="form-control" value="<?= clean($v['vehicle_number']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= clean($v['brand']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Type</label>
                    <select name="type_id" class="form-select">
                      <option value="">Select Type</option>
                      <?php foreach ($vehicleTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $v['type_id'] == $t['id'] ? 'selected' : '' ?>><?= clean($t['type_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Price Per Day (PKR) *</label>
                    <input type="number" name="price_per_day" class="form-control" value="<?= $v['price_per_day'] ?>" min="0" step="0.01" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                      <?php foreach (['Available','Rented','Maintenance'] as $s): ?>
                        <option value="<?= $s ?>" <?= $v['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= clean($v['description']) ?></textarea>
                  </div>
                </div>
              </div>
            </div>
            <div class="data-card">
              <div class="data-card-header"><h5 class="data-card-title">Specifications</h5></div>
              <div class="data-card-body">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Seats</label>
                    <input type="number" name="seats" class="form-control" min="1" max="20" value="<?= $v['seats'] ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= $v['year'] ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Fuel Type</label>
                    <select name="fuel_type" class="form-select">
                      <?php foreach (['Petrol','Diesel','Electric','Hybrid','CNG'] as $f): ?>
                        <option value="<?= $f ?>" <?= $v['fuel_type'] === $f ? 'selected' : '' ?>><?= $f ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Transmission</label>
                    <select name="transmission" class="form-select">
                      <option value="Manual"    <?= $v['transmission'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                      <option value="Automatic" <?= $v['transmission'] === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="data-card">
              <div class="data-card-header"><h5 class="data-card-title">Vehicle Image</h5></div>
              <div class="data-card-body">
                <?php
                $imgPath = '../assets/images/vehicles/' . $v['image'];
                $previewSrc = ($v['image'] && $v['image'] !== 'default.jpg' && file_exists($imgPath)) ? $imgPath : '../assets/images/car-placeholder.svg';
                ?>
                <div class="mb-3 text-center">
                  <img id="imagePreview" src="<?= $previewSrc ?>" alt="Preview"
                       style="width:100%;height:160px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.03);border:1px solid var(--dark-border);padding:10px;">
                </div>
                <label class="upload-zone d-block" for="imageInput">
                  <i class="bi bi-cloud-upload-fill"></i>
                  <span style="font-size:13px;">Click to change image</span>
                  <small class="d-block mt-1">Leave empty to keep current</small>
                </label>
                <input type="file" id="imageInput" name="image" accept="image/*" class="d-none" onchange="previewImage(this, 'imagePreview')">
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary-glow px-5"><i class="bi bi-check2-circle me-2"></i>Update Vehicle</button>
              <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-glass px-4">Cancel</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>