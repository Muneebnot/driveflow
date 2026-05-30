<?php
// customer/rent.php
require_once '../includes/config.php';
requireLogin('customer');

$db  = getDB();
$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','Invalid vehicle.'); redirect(SITE_URL.'/customer/browse.php'); }

$stmt = $db->prepare("SELECT v.*, vt.type_name FROM vehicles v LEFT JOIN vehicle_types vt ON v.type_id=vt.id WHERE v.id=?");
$stmt->execute([$id]);
$v = $stmt->fetch();

if (!$v)              { setFlash('danger','Vehicle not found.'); redirect(SITE_URL.'/customer/browse.php'); }
if ($v['status'] !== 'Available') { setFlash('warning','This vehicle is not currently available.'); redirect(SITE_URL.'/customer/browse.php'); }

$errors = [];
$today  = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date']   ?? '';
    $notes      = trim($_POST['notes'] ?? '');
    $pay_method = $_POST['payment_method'] ?? 'Cash';

    if (empty($start_date)) $errors[] = 'Start date is required.';
    if (empty($end_date))   $errors[] = 'End date is required.';
    if ($start_date && $end_date) {
        if ($start_date < $today)      $errors[] = 'Start date cannot be in the past.';
        if ($end_date <= $start_date)  $errors[] = 'End date must be after start date.';
    }

    if (empty($errors)) {
        $days       = (int)((strtotime($end_date) - strtotime($start_date)) / 86400);
        $totalPrice = $days * $v['price_per_day'];

        // Check no overlapping rental
        $overlap = $db->prepare("
            SELECT COUNT(*) FROM rentals
            WHERE vehicle_id=? AND rental_status IN ('Approved','Active')
              AND NOT (end_date <= ? OR start_date >= ?)
        ");
        $overlap->execute([$id, $start_date, $end_date]);
        if ($overlap->fetchColumn() > 0) {
            $errors[] = 'Vehicle is already booked for this date range. Please choose different dates.';
        }
    }

    if (empty($errors)) {
        $ins = $db->prepare("INSERT INTO rentals (user_id, vehicle_id, start_date, end_date, total_price, rental_status, notes) VALUES (?,?,?,?,?,'Pending',?)");
        $ins->execute([$uid, $id, $start_date, $end_date, $totalPrice, $notes]);
        $rentalId = $db->lastInsertId();

        // Create payment record
        $db->prepare("INSERT INTO payments (rental_id, amount, payment_method, payment_status) VALUES (?,?,?,'Pending')")->execute([$rentalId, $totalPrice, $pay_method]);

        logActivity($uid, "Booked rental for {$v['vehicle_name']} ({$start_date} to {$end_date})");
        createNotification($uid, 'Booking Submitted!', "Your booking for {$v['vehicle_name']} is pending admin approval.");

        // Notify admin
        $db->prepare("INSERT INTO notifications (title, message) VALUES (?,?)")->execute(['New Rental Request', "Customer {$_SESSION['full_name']} has requested {$v['vehicle_name']} from {$start_date} to {$end_date}."]);

        setFlash('success', "Booking submitted! Your rental request is pending approval.");
        redirect(SITE_URL.'/customer/my_rentals.php');
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book <?= clean($v['vehicle_name']) ?> – DriveFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<link href="../assets/css/customer.css" rel="stylesheet">
</head><body>
<?php include '../includes/customer_nav.php'; ?>

<div class="customer-wrapper">
  <div class="container py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
      <a href="<?= SITE_URL ?>/customer/browse.php" class="btn btn-glass btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
      <div>
        <h4 class="mb-0">Book Vehicle</h4>
        <p style="color:var(--text-muted);font-size:13px;margin:0;"><?= clean($v['vehicle_name']) ?></p>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <?php foreach ($errors as $e): ?><div><i class="bi bi-x-circle me-2"></i><?= clean($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Vehicle Details -->
      <div class="col-lg-7">
        <div class="data-card mb-4">
          <?php
          $imgPath = '../assets/images/vehicles/'.$v['image'];
          $imgUrl  = ($v['image'] && $v['image'] !== 'default.jpg' && file_exists($imgPath)) ? $imgPath : '../assets/images/car-placeholder.svg';
          ?>
          <img src="<?= $imgUrl ?>" alt="<?= clean($v['vehicle_name']) ?>" class="vehicle-detail-img">
          <div class="data-card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
              <div>
                <h3 class="mb-1"><?= clean($v['vehicle_name']) ?></h3>
                <p style="color:var(--text-muted);margin:0;font-size:14px;"><?= clean($v['brand']) ?> · <?= $v['year'] ?> · <?= clean($v['type_name'] ?? 'Vehicle') ?></p>
              </div>
              <span class="vehicle-status-badge status-available" style="position:static;">Available</span>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
              <span class="spec-badge"><i class="bi bi-people-fill"></i><?= $v['seats'] ?> Seats</span>
              <span class="spec-badge"><i class="bi bi-fuel-pump"></i><?= $v['fuel_type'] ?></span>
              <span class="spec-badge"><i class="bi bi-gear"></i><?= $v['transmission'] ?></span>
              <span class="spec-badge"><i class="bi bi-calendar3"></i><?= $v['year'] ?></span>
            </div>

            <?php if ($v['description']): ?>
              <p style="color:var(--text-secondary);font-size:14px;line-height:1.7;"><?= clean($v['description']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Booking Form -->
      <div class="col-lg-5">
        <div class="booking-card">
          <div class="d-flex align-items-baseline gap-2 mb-4">
            <span class="price-big"><?= formatCurrency($v['price_per_day']) ?></span>
            <span style="color:var(--text-muted);font-size:13px;">/day</span>
          </div>

          <form method="POST" id="bookingForm">
            <div class="mb-3">
              <label class="form-label">Pick-up Date</label>
              <input type="date" name="start_date" id="startDate" class="form-control"
                     min="<?= $today ?>" value="<?= clean($_POST['start_date'] ?? '') ?>" required onchange="calcTotal()">
            </div>
            <div class="mb-3">
              <label class="form-label">Return Date</label>
              <input type="date" name="end_date" id="endDate" class="form-control"
                     min="<?= date('Y-m-d', strtotime($today.'+1 day')) ?>" value="<?= clean($_POST['end_date'] ?? '') ?>" required onchange="calcTotal()">
            </div>
            <div class="mb-3">
              <label class="form-label">Payment Method</label>
              <select name="payment_method" class="form-select">
                <option value="Cash">Cash on Pickup</option>
                <option value="Card">Credit/Debit Card</option>
                <option value="Online">Online Transfer</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Special Requests <span style="color:var(--text-muted);">(Optional)</span></label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Any special requirements..."><?= clean($_POST['notes'] ?? '') ?></textarea>
            </div>

            <!-- Price Summary -->
            <hr class="booking-divider">
            <div id="priceSummary" style="display:none;">
              <div class="booking-summary-row"><span>Price per day</span><span><?= formatCurrency($v['price_per_day']) ?></span></div>
              <div class="booking-summary-row"><span>Number of days</span><span id="daysCount">—</span></div>
              <div class="booking-summary-row total"><span>Total Amount</span><span id="totalAmount" style="color:var(--accent-blue);">—</span></div>
              <hr class="booking-divider">
            </div>

            <button type="submit" class="btn btn-primary-glow w-100 py-2">
              <i class="bi bi-calendar2-check-fill me-2"></i>Confirm Booking
            </button>
            <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:10px;">
              <i class="bi bi-shield-check me-1"></i>Booking is subject to admin approval
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
const pricePerDay = <?= $v['price_per_day'] ?>;

function calcTotal() {
  const s = document.getElementById('startDate').value;
  const e = document.getElementById('endDate').value;
  const summary = document.getElementById('priceSummary');
  if (s && e && e > s) {
    const days = Math.round((new Date(e) - new Date(s)) / 86400000);
    const total = days * pricePerDay;
    document.getElementById('daysCount').textContent = days + ' day' + (days>1?'s':'');
    document.getElementById('totalAmount').textContent = 'PKR ' + total.toLocaleString();
    summary.style.display = 'block';
    // Update end date min
    document.getElementById('endDate').min = new Date(new Date(s).getTime() + 86400000).toISOString().split('T')[0];
  } else {
    summary.style.display = 'none';
  }
}
// Run on load if values exist
calcTotal();
</script>
</body></html>