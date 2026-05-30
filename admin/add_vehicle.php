<?php
// admin/add_vehicle.php
require_once '../includes/config.php';
requireLogin('admin');

$db = getDB();
$vehicleTypes = $db->query("SELECT * FROM vehicle_types ORDER BY type_name")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_name  = trim($_POST['vehicle_name'] ?? '');
    $vehicle_number= trim($_POST['vehicle_number'] ?? '');
    $type_id       = (int)($_POST['type_id'] ?? 0);
    $brand         = trim($_POST['brand'] ?? '');
    $price_per_day = (float)($_POST['price_per_day'] ?? 0);
    $status        = $_POST['status'] ?? 'Available';
    $description   = trim($_POST['description'] ?? '');
    $seats         = (int)($_POST['seats'] ?? 5);
    $fuel_type     = trim($_POST['fuel_type'] ?? 'Petrol');
    $transmission  = trim($_POST['transmission'] ?? 'Manual');
    $year          = (int)($_POST['year'] ?? date('Y'));

    if (empty($vehicle_name))   $errors[] = 'Vehicle name is required.';
    if (empty($vehicle_number)) $errors[] = 'Vehicle number is required.';
    if ($price_per_day <= 0)    $errors[] = 'Price per day must be greater than 0.';

    // Check duplicate vehicle number
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM vehicles WHERE vehicle_number=?");
        $chk->execute([$vehicle_number]);
        if ($chk->fetch()) $errors[] = 'Vehicle number already exists.';
    }

    // Handle image upload
    $imageName = 'default.jpg';
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image format. Use JPG, PNG, or WebP.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image too large. Max 2MB.';
        } else {
            $uploadDir = '../assets/images/vehicles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $imageName = uniqid('veh_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO vehicles (vehicle_name, vehicle_number, type_id, brand, price_per_day, status, image, description, seats, fuel_type, transmission, year) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$vehicle_name, $vehicle_number, $type_id ?: null, $brand, $price_per_day, $status, $imageName, $description, $seats, $fuel_type, $transmission, $year]);
        $newId = $db->lastInsertId();
        logActivity($_SESSION['user_id'], "Added new vehicle: $vehicle_name ($vehicle_number)");
        setFlash('success', "Vehicle '$vehicle_name' added successfully!");
        redirect(SITE_URL . '/admin/vehicles.php');
    }
}

$pageTitle = 'Add Vehicle';
$breadcrumb = 'Add Vehicle';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Vehicle – DriveFlow Admin</title>
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
        <div>
          <h4 class="mb-1">Add New Vehicle</h4>
          <p style="color:var(--text-muted);font-size:13px;margin:0;">Add a vehicle to the DriveFlow fleet</p>
        </div>
        <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-glass"><i class="bi bi-arrow-left me-2"></i>Back</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
          <?php foreach ($errors as $e): ?><div><i class="bi bi-x-circle me-2"></i><?= clean($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
          <!-- Left Column -->
          <div class="col-lg-8">
            <div class="data-card mb-4">
              <div class="data-card-header"><h5 class="data-card-title">Vehicle Information</h5></div>
              <div class="data-card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Name *</label>
                    <input type="text" name="vehicle_name" class="form-control" placeholder="e.g. Corolla GLI" value="<?= clean($_POST['vehicle_name'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. KHI-2024-001" value="<?= clean($_POST['vehicle_number'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" placeholder="e.g. Toyota" value="<?= clean($_POST['brand'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Vehicle Type</label>
                    <select name="type_id" class="form-select">
                      <option value="">Select Type</option>
                      <?php foreach ($vehicleTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($_POST['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= clean($t['type_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Price Per Day (PKR) *</label>
                    <input type="number" name="price_per_day" class="form-control" placeholder="e.g. 5000" min="0" step="0.01" value="<?= $_POST['price_per_day'] ?? '' ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                      <option value="Available" <?= ($_POST['status'] ?? 'Available') === 'Available' ? 'selected' : '' ?>>Available</option>
                      <option value="Rented"    <?= ($_POST['status'] ?? '') === 'Rented' ? 'selected' : '' ?>>Rented</option>
                      <option value="Maintenance" <?= ($_POST['status'] ?? '') === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the vehicle..."><?= clean($_POST['description'] ?? '') ?></textarea>
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
                    <input type="number" name="seats" class="form-control" min="1" max="20" value="<?= $_POST['seats'] ?? 5 ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" min="2000" max="<?= date('Y')+1 ?>" value="<?= $_POST['year'] ?? date('Y') ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Fuel Type</label>
                    <select name="fuel_type" class="form-select">
                      <?php foreach (['Petrol','Diesel','Electric','Hybrid','CNG'] as $f): ?>
                        <option value="<?= $f ?>" <?= ($_POST['fuel_type'] ?? 'Petrol') === $f ? 'selected' : '' ?>><?= $f ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Transmission</label>
                    <select name="transmission" class="form-select">
                      <option value="Manual"    <?= ($_POST['transmission'] ?? 'Manual') === 'Manual' ? 'selected' : '' ?>>Manual</option>
                      <option value="Automatic" <?= ($_POST['transmission'] ?? '') === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Image -->
          <div class="col-lg-4">
            <div class="data-card">
              <div class="data-card-header"><h5 class="data-card-title">Vehicle Image</h5></div>
              <div class="data-card-body">
                <div class="mb-3 text-center">
                  <img id="imagePreview" src="../assets/images/car-placeholder.svg" alt="Preview"
                       style="width:100%;height:160px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.03);border:1px solid var(--dark-border);padding:10px;">
                </div>
                <label class="upload-zone d-block" for="imageInput">
                  <i class="bi bi-cloud-upload-fill"></i>
                  <span style="font-size:13px;">Click to upload image</span>
                  <small class="d-block mt-1">JPG, PNG, WebP · Max 2MB</small>
                </label>
                <input type="file" id="imageInput" name="image" accept="image/*" class="d-none"
                       onchange="previewImage(this, 'imagePreview')">
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="col-12">
            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary-glow px-5">
                <i class="bi bi-plus-circle-fill me-2"></i>Add Vehicle
              </button>
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