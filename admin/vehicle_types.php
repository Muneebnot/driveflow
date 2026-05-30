<?php
// admin/vehicle_types.php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM vehicle_types WHERE id=?")->execute([$id]);
    setFlash('success','Vehicle type deleted.'); redirect(SITE_URL.'/admin/vehicle_types.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_name   = trim($_POST['type_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $edit_id     = (int)($_POST['edit_id'] ?? 0);
    if (empty($type_name)) $errors[] = 'Type name is required.';
    if (empty($errors)) {
        if ($edit_id) {
            $db->prepare("UPDATE vehicle_types SET type_name=?, description=? WHERE id=?")->execute([$type_name,$description,$edit_id]);
            setFlash('success','Type updated.');
        } else {
            $db->prepare("INSERT INTO vehicle_types (type_name, description) VALUES (?,?)")->execute([$type_name,$description]);
            setFlash('success','New type added.');
        }
        redirect(SITE_URL.'/admin/vehicle_types.php');
    }
}

$types = $db->query("SELECT vt.*, COUNT(v.id) as vehicle_count FROM vehicle_types vt LEFT JOIN vehicles v ON v.type_id=vt.id GROUP BY vt.id ORDER BY vt.type_name")->fetchAll();
$editType = null;
if (isset($_GET['edit'])) {
    $et = $db->prepare("SELECT * FROM vehicle_types WHERE id=?"); $et->execute([(int)$_GET['edit']]); $editType = $et->fetch();
}

$pageTitle = 'Vehicle Types'; $breadcrumb = 'Vehicle Types';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Types – DriveFlow Admin</title>
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
      <?php $flash = getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div><?php endif; ?>

      <div class="row g-4">
        <!-- Form -->
        <div class="col-lg-5">
          <div class="data-card">
            <div class="data-card-header"><h5 class="data-card-title"><?= $editType ? 'Edit Type' : 'Add Vehicle Type' ?></h5></div>
            <div class="data-card-body">
              <?php if (!empty($errors)): ?><div class="alert alert-danger mb-3"><?= implode('<br>', array_map('clean',$errors)) ?></div><?php endif; ?>
              <form method="POST">
                <?php if ($editType): ?><input type="hidden" name="edit_id" value="<?= $editType['id'] ?>"><?php endif; ?>
                <div class="mb-3">
                  <label class="form-label">Type Name *</label>
                  <input type="text" name="type_name" class="form-control" value="<?= clean($editType['type_name'] ?? '') ?>" placeholder="e.g. SUV" required>
                </div>
                <div class="mb-4">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."><?= clean($editType['description'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary-glow"><?= $editType ? 'Update Type' : 'Add Type' ?></button>
                  <?php if ($editType): ?><a href="<?= SITE_URL ?>/admin/vehicle_types.php" class="btn btn-glass">Cancel</a><?php endif; ?>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- List -->
        <div class="col-lg-7">
          <div class="data-card">
            <div class="data-card-header"><h5 class="data-card-title">All Vehicle Types</h5></div>
            <div class="data-card-body">
              <table class="df-table">
                <thead><tr><th>Type</th><th>Description</th><th>Vehicles</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($types as $t): ?>
                <tr>
                  <td><strong style="font-size:13px;"><?= clean($t['type_name']) ?></strong></td>
                  <td style="font-size:12px;color:var(--text-muted);"><?= clean(substr($t['description'] ?? '',0,50)) ?>...</td>
                  <td><span style="background:rgba(59,130,246,0.1);color:var(--accent-blue);padding:2px 8px;border-radius:4px;font-size:12px;"><?= $t['vehicle_count'] ?></span></td>
                  <td>
                    <div class="d-flex gap-2">
                      <a href="?edit=<?= $t['id'] ?>" class="btn btn-glass btn-sm"><i class="bi bi-pencil-fill"></i></a>
                      <?php if ($t['vehicle_count'] == 0): ?>
                      <button onclick="confirmDelete('<?= SITE_URL ?>/admin/vehicle_types.php?delete=<?= $t['id'] ?>')" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;border-radius:8px;"><i class="bi bi-trash-fill"></i></button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>