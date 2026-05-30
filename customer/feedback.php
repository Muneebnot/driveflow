<?php
// customer/feedback.php
require_once '../includes/config.php';
requireLogin('customer');

$db  = getDB();
$uid = $_SESSION['user_id'];

// Pre-fill vehicle from query param
$preVehicleId = (int)($_GET['vehicle_id'] ?? 0);

// Get vehicles the customer has completed rentals for
$myVehicles = $db->prepare("SELECT DISTINCT v.id, v.vehicle_name, v.brand FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id WHERE r.user_id=? AND r.rental_status='Completed' ORDER BY v.vehicle_name");
$myVehicles->execute([$uid]);
$myVehicles = $myVehicles->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $message    = trim($_POST['message'] ?? '');
    $rating     = (int)($_POST['rating'] ?? 0);

    if (empty($message))          $errors[] = 'Please write your feedback message.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a rating between 1 and 5.';

    if (empty($errors)) {
        $ins = $db->prepare("INSERT INTO feedback (user_id, vehicle_id, message, rating) VALUES (?,?,?,?)");
        $ins->execute([$uid, $vehicle_id ?: null, $message, $rating]);
        logActivity($uid, "Submitted feedback with rating {$rating}/5");
        setFlash('success', 'Thank you for your feedback! Your review has been submitted.');
        redirect(SITE_URL.'/customer/feedback.php');
    }
}

// Previous feedback from this user
$prevFeedback = $db->prepare("SELECT f.*, v.vehicle_name FROM feedback f LEFT JOIN vehicles v ON f.vehicle_id=v.id WHERE f.user_id=? ORDER BY f.created_at DESC");
$prevFeedback->execute([$uid]);
$prevFeedback = $prevFeedback->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head><body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?> flash-alert mb-4"><?= clean($flash['message']) ?></div>
    <?php endif; ?>

    <div class="mb-4"><h4 class="mb-1">Leave Feedback</h4><p style="color:var(--text-muted);font-size:13px;margin:0;">Share your experience with DriveFlow</p></div>

    <div class="row g-4">
      <!-- Form -->
      <div class="col-lg-6">
        <div class="data-card">
          <div class="data-card-header"><h5 class="data-card-title">Write a Review</h5></div>
          <div class="data-card-body">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger mb-4">
                <?php foreach ($errors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="POST">
              <!-- Star Rating -->
              <div class="mb-4">
                <label class="form-label">Your Rating *</label>
                <div class="star-rating-wrap d-flex gap-2 align-items-center">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" class="d-none star-input" <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?>>
                  <label for="star<?= $i ?>" class="star-label">
                    <i class="bi bi-star-fill"></i>
                  </label>
                  <?php endfor; ?>
                  <span id="ratingText" style="font-size:13px;color:var(--text-muted);margin-left:8px;">Select a rating</span>
                </div>
              </div>

              <!-- Vehicle (optional) -->
              <div class="mb-3">
                <label class="form-label">Vehicle <span style="color:var(--text-muted);">(Optional)</span></label>
                <select name="vehicle_id" class="form-select">
                  <option value="">General Feedback</option>
                  <?php foreach ($myVehicles as $mv): ?>
                    <option value="<?= $mv['id'] ?>" <?= ($preVehicleId == $mv['id'] || ($_POST['vehicle_id'] ?? 0) == $mv['id']) ? 'selected' : '' ?>>
                      <?= clean($mv['vehicle_name']) ?> (<?= clean($mv['brand']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($myVehicles)): ?>
                  <small style="color:var(--text-muted);font-size:11px;">Complete a rental first to leave vehicle-specific feedback.</small>
                <?php endif; ?>
              </div>

              <!-- Message -->
              <div class="mb-4">
                <label class="form-label">Your Review *</label>
                <textarea name="message" class="form-control" rows="5" placeholder="Tell us about your experience..." required><?= clean($_POST['message'] ?? '') ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary-glow w-100">
                <i class="bi bi-send-fill me-2"></i>Submit Feedback
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Previous reviews -->
      <div class="col-lg-6">
        <div class="data-card">
          <div class="data-card-header"><h5 class="data-card-title">My Reviews</h5></div>
          <div class="data-card-body">
            <?php if (empty($prevFeedback)): ?>
              <div class="empty-state" style="padding:40px 20px;">
                <i class="bi bi-chat-square"></i>
                <h5>No Reviews Yet</h5>
                <p>Share your first review!</p>
              </div>
            <?php else: ?>
              <?php foreach ($prevFeedback as $f): ?>
              <div class="glass-card mb-3" style="padding:16px;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <?php if ($f['vehicle_name']): ?>
                      <strong style="font-size:13px;"><?= clean($f['vehicle_name']) ?></strong>
                    <?php else: ?>
                      <strong style="font-size:13px;">General Feedback</strong>
                    <?php endif; ?>
                  </div>
                  <div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="bi bi-star<?= $i <= $f['rating'] ? '-fill' : '' ?> text-warning" style="font-size:13px;"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <p style="color:var(--text-secondary);font-size:13px;margin-bottom:8px;font-style:italic;">"<?= clean($f['message']) ?>"</p>
                <small style="color:var(--text-muted);font-size:11px;"><?= date('M d, Y', strtotime($f['created_at'])) ?></small>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<style>
.star-rating-wrap { flex-direction: row-reverse; justify-content: flex-end; }
.star-label { font-size: 1.8rem; color: var(--text-muted); cursor: pointer; transition: color 0.15s; }
.star-input:checked ~ .star-label,
.star-label:hover,
.star-label:hover ~ .star-label { color: #f59e0b; }
</style>
<script>
const ratingTexts = ['','Terrible','Poor','Good','Very Good','Excellent'];
document.querySelectorAll('.star-input').forEach(input => {
  input.addEventListener('change', () => {
    document.getElementById('ratingText').textContent = ratingTexts[input.value] + ' (' + input.value + '/5)';
    document.getElementById('ratingText').style.color = '#f59e0b';
  });
});
</script>
</body></html>