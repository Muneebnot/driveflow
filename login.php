<?php
// login.php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            logActivity($user['id'], "User logged in: {$user['full_name']}");

            if ($user['role'] === 'admin') redirect('admin/dashboard.php');
            else redirect('customer/dashboard.php');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
  <!-- Background orbs -->
  <div class="auth-orb" style="width:400px;height:400px;background:radial-gradient(circle,rgba(59,130,246,0.15),transparent);top:-100px;left:-100px;"></div>
  <div class="auth-orb" style="width:300px;height:300px;background:radial-gradient(circle,rgba(139,92,246,0.12),transparent);bottom:-50px;right:-50px;"></div>

  <div class="auth-card animate-fadeInUp">
    <!-- Logo -->
    <div class="auth-logo">
      <span class="brand-icon" style="font-size:2.5rem;"><i class="bi bi-lightning-charge-fill"></i></span>
      <div class="navbar-brand" style="font-size:1.8rem;">Drive<span class="brand-accent">Flow</span></div>
    </div>

    <h2>Welcome Back</h2>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= clean($error) ?></div>
    <?php endif; ?>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?> mb-4"><?= clean($flash['message']) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-4">
        <label class="form-label">Email Address</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-envelope-fill"></i></span>
          <input type="email" name="email" class="form-control" style="padding-left:36px;" placeholder="you@example.com" value="<?= clean($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="password" id="passwordField" class="form-control" style="padding-left:36px;padding-right:40px;" placeholder="••••••••" required>
          <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
            <i class="bi bi-eye-fill" id="passIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary-glow w-100 py-2 mb-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <p class="text-center" style="font-size:14px;color:var(--text-muted);">
      Don't have an account? <a href="register.php" style="color:var(--accent-blue);text-decoration:none;font-weight:600;">Create one free</a>
    </p>

    <!-- Demo credentials hint -->
    <div class="mt-4 p-3" style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.15);border-radius:10px;">
      <p style="font-size:12px;color:var(--text-muted);margin:0;text-align:center;">
        <i class="bi bi-info-circle me-1"></i>
        <strong style="color:var(--text-secondary);">Demo:</strong>
        Admin: <code style="color:var(--accent-blue);">admin@driveflow.com</code> &nbsp;|&nbsp;
        Customer: <code style="color:var(--accent-blue);">ali@example.com</code><br>
        Password: <code style="color:var(--accent-cyan);">password</code>
      </p>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
  const f = document.getElementById('passwordField');
  const i = document.getElementById('passIcon');
  if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash-fill'; }
  else { f.type = 'password'; i.className = 'bi bi-eye-fill'; }
}
</script>
</body>
</html>