<?php
// customer/profile.php
require_once '../includes/config.php';
requireLogin('customer');

$db  = getDB();
$uid = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$uid]); $user = $stmt->fetch();

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        if (empty($full_name)) $errors[] = 'Name is required.';
        if (empty($errors)) {
            $db->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?")->execute([$full_name, $phone, $address, $uid]);
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully!';
            $stmt->execute([$uid]); $user = $stmt->fetch();
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
        if ($new !== $confirm) $errors[] = 'Passwords do not match.';
        if (empty($errors)) {
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            $success = 'Password changed successfully!';
        }
    }
}

// Stats
$totalRentals = $db->prepare("SELECT COUNT(*) FROM rentals WHERE user_id=?"); $totalRentals->execute([$uid]); $totalRentals = $totalRentals->fetchColumn();
$totalSpent   = $db->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN rentals r ON p.rental_id=r.id WHERE r.user_id=? AND p.payment_status='Completed'"); $totalSpent->execute([$uid]); $totalSpent = $totalSpent->fetchColumn();
$myRatings    = $db->prepare("SELECT COUNT(*) FROM feedback WHERE user_id=?"); $myRatings->execute([$uid]); $myRatings = $myRatings->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head><body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">
    <div class="mb-4"><h4 class="mb-1">My Profile</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">Manage your account settings</p></div>

    <?php if (!empty($errors)): ?><div class="alert alert-danger mb-4"><?php foreach ($errors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success mb-4"><?= clean($success) ?></div><?php endif; ?>

    <div class="row g-4">
      <!-- Profile Card -->
      <div class="col-lg-4">
        <div class="data-card text-center">
          <div class="data-card-body">
            <div style="width:80px;height:80px;background:var(--gradient-1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;margin:0 auto 16px;">
              <?= strtoupper(substr($user['full_name'],0,1)) ?>
            </div>
            <h5 class="mb-1"><?= clean($user['full_name']) ?></h5>
            <p style="color:var(--text-muted);font-size:13px;"><?= clean($user['email']) ?></p>
            <span class="badge-approved" style="display:inline-block;margin-bottom:16px;">Customer</span>
            <div class="row g-2">
              <div class="col-4">
                <div style="background:rgba(59,130,246,0.08);border-radius:10px;padding:12px 8px;">
                  <div style="font-size:1.2rem;font-weight:800;color:var(--accent-blue);"><?= $totalRentals ?></div>
                  <div style="font-size:10px;color:var(--text-muted);">Rentals</div>
                </div>
              </div>
              <div class="col-4">
                <div style="background:rgba(16,185,129,0.08);border-radius:10px;padding:12px 8px;">
                  <div style="font-size:0.9rem;font-weight:800;color:var(--accent-green);"><?= number_format($totalSpent/1000,1) ?>K</div>
                  <div style="font-size:10px;color:var(--text-muted);">Spent</div>
                </div>
              </div>
              <div class="col-4">
                <div style="background:rgba(245,158,11,0.08);border-radius:10px;padding:12px 8px;">
                  <div style="font-size:1.2rem;font-weight:800;color:var(--accent-orange);"><?= $myRatings ?></div>
                  <div style="font-size:10px;color:var(--text-muted);">Reviews</div>
                </div>
              </div>
            </div>
            <p style="font-size:12px;color:var(--text-muted);margin-top:12px;margin-bottom:0;">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
          </div>
        </div>
      </div>

      <!-- Edit Forms -->
      <div class="col-lg-8">
        <!-- Update Profile -->
        <div class="data-card mb-4">
          <div class="data-card-header"><h5 class="data-card-title">Personal Information</h5></div>
          <div class="data-card-body">
            <form method="POST">
              <input type="hidden" name="action" value="profile">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email <span style="color:var(--text-muted);">(Read-only)</span></label>
                  <input type="email" class="form-control" value="<?= clean($user['email']) ?>" readonly style="opacity:0.5;">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" name="phone" class="form-control" value="<?= clean($user['phone'] ?? '') ?>" placeholder="+92 300 1234567">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Address</label>
                  <input type="text" name="address" class="form-control" value="<?= clean($user['address'] ?? '') ?>" placeholder="Your address">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary-glow"><i class="bi bi-check2-circle me-2"></i>Update Profile</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="data-card">
          <div class="data-card-header"><h5 class="data-card-title">Change Password</h5></div>
          <div class="data-card-body">
            <form method="POST">
              <input type="hidden" name="action" value="password">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Current Password</label>
                  <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">New Password</label>
                  <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-glass"><i class="bi bi-lock me-2"></i>Change Password</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>