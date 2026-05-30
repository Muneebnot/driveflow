<?php
// register.php
require_once 'includes/config.php';

if (isLoggedIn()) redirect('customer/dashboard.php');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name))     $errors[] = 'Full name is required.';
    if (empty($email))         $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (empty($password))      $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$full_name, $email, $hashed, $phone]);
            $userId = $db->lastInsertId();

            logActivity($userId, "New customer registered: $full_name");
            createNotification($userId, 'Welcome to DriveFlow!', "Hi $full_name, your account is ready. Start browsing our premium fleet!");

            setFlash('success', 'Account created successfully! Please sign in.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
  <div class="auth-orb" style="width:400px;height:400px;background:radial-gradient(circle,rgba(59,130,246,0.15),transparent);top:-100px;right:-100px;"></div>
  <div class="auth-orb" style="width:300px;height:300px;background:radial-gradient(circle,rgba(16,185,129,0.1),transparent);bottom:-50px;left:-50px;"></div>

  <div class="auth-card animate-fadeInUp" style="max-width:500px;">
    <div class="auth-logo">
      <span class="brand-icon" style="font-size:2.5rem;"><i class="bi bi-lightning-charge-fill"></i></span>
      <div class="navbar-brand" style="font-size:1.8rem;">Drive<span class="brand-accent">Flow</span></div>
    </div>

    <h2>Create Account</h2>
    <p class="auth-subtitle">Join thousands of happy DriveFlow customers</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php foreach ($errors as $e): ?><?= clean($e) ?><br><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-person-fill"></i></span>
          <input type="text" name="full_name" class="form-control" style="padding-left:36px;" placeholder="Muhammad Ali" value="<?= clean($_POST['full_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-envelope-fill"></i></span>
          <input type="email" name="email" class="form-control" style="padding-left:36px;" placeholder="you@example.com" value="<?= clean($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone Number <span style="color:var(--text-muted);">(Optional)</span></label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-phone-fill"></i></span>
          <input type="tel" name="phone" class="form-control" style="padding-left:36px;" placeholder="+92 300 1234567" value="<?= clean($_POST['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="password" id="passwordField" class="form-control" style="padding-left:36px;padding-right:40px;" placeholder="Min. 6 characters" required>
          <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
            <i class="bi bi-eye-fill" id="passIcon"></i>
          </button>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Confirm Password</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="confirm_password" class="form-control" style="padding-left:36px;" placeholder="Repeat password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary-glow w-100 py-2 mb-4">
        <i class="bi bi-person-plus-fill me-2"></i>Create Account
      </button>
    </form>

    <p class="text-center" style="font-size:14px;color:var(--text-muted);">
      Already have an account? <a href="login.php" style="color:var(--accent-blue);text-decoration:none;font-weight:600;">Sign in</a>
    </p>
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