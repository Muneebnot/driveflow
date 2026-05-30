<?php
// index.php — DriveFlow Homepage
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') redirect('admin/dashboard.php');
    else redirect('customer/dashboard.php');
}

$db = getDB();
// Get stats for homepage
$totalVehicles = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalRentals = $db->query("SELECT COUNT(*) FROM rentals WHERE rental_status='Completed'")->fetchColumn();

// Get featured vehicles
$featuredVehicles = $db->query("SELECT v.*, vt.type_name FROM vehicles v LEFT JOIN vehicle_types vt ON v.type_id=vt.id WHERE v.status='Available' ORDER BY v.price_per_day DESC LIMIT 6")->fetchAll();

// Get feedback/reviews
$reviews = $db->query("SELECT f.*, u.full_name, v.vehicle_name FROM feedback f JOIN users u ON f.user_id=u.id LEFT JOIN vehicles v ON f.vehicle_id=v.id ORDER BY f.created_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DriveFlow – Premium Vehicle Rental</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="homepage">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
      Drive<span class="brand-accent">Flow</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <li class="nav-item"><a class="nav-link" href="#fleet">Fleet</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#reviews">Reviews</a></li>
        <li class="nav-item"><a class="btn btn-outline-light btn-sm px-3" href="login.php">Sign In</a></li>
        <li class="nav-item"><a class="btn btn-primary-glow btn-sm px-3" href="register.php">Get Started</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
  <div class="hero-bg-grid"></div>
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="container text-center position-relative z-2">
    <div class="hero-badge mb-4">
      <span><i class="bi bi-stars"></i> Pakistan's #1 Fleet Management Platform</span>
    </div>
    <h1 class="hero-title">Drive Your Future<br><span class="gradient-text">Without Limits</span></h1>
    <p class="hero-subtitle">Premium vehicles. Seamless rentals. Real-time fleet management.<br>Experience the future of mobility with DriveFlow.</p>
    <div class="hero-cta d-flex gap-3 justify-content-center flex-wrap">
      <a href="register.php" class="btn btn-primary-glow btn-lg px-5">
        <i class="bi bi-rocket-takeoff me-2"></i>Start Renting
      </a>
      <a href="#fleet" class="btn btn-glass btn-lg px-5">
        <i class="bi bi-car-front me-2"></i>View Fleet
      </a>
    </div>
    <div class="hero-stats d-flex justify-content-center gap-5 mt-5 flex-wrap">
      <div class="stat-pill">
        <span class="stat-num" data-target="<?= $totalVehicles ?>"><?= $totalVehicles ?></span>
        <span class="stat-label">Available Vehicles</span>
      </div>
      <div class="stat-pill">
        <span class="stat-num" data-target="<?= $totalCustomers ?>"><?= $totalCustomers ?>+</span>
        <span class="stat-label">Happy Customers</span>
      </div>
      <div class="stat-pill">
        <span class="stat-num" data-target="<?= $totalRentals ?>"><?= $totalRentals ?>+</span>
        <span class="stat-label">Rentals Completed</span>
      </div>
    </div>
  </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section py-5" id="features">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Why DriveFlow?</span>
      <h2>Everything You Need,<br><span class="gradient-text">Nothing You Don't</span></h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-shield-check-fill"></i></div>
          <h4>Fully Insured Fleet</h4>
          <p>Every vehicle is comprehensively insured. Drive with complete peace of mind on every journey.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
          <h4>Instant Booking</h4>
          <p>Book your preferred vehicle in under 2 minutes. Real-time availability, instant confirmation.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-headset"></i></div>
          <h4>24/7 Support</h4>
          <p>Round-the-clock customer support. We're always here when you need us, day or night.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
          <h4>Best Price Guarantee</h4>
          <p>Transparent pricing with no hidden fees. Find a better price? We'll match it.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h4>Fleet Analytics</h4>
          <p>Powerful real-time analytics dashboard for businesses managing large vehicle fleets.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card glass-card h-100">
          <div class="feature-icon"><i class="bi bi-phone-fill"></i></div>
          <h4>Mobile Optimized</h4>
          <p>Manage your rentals on-the-go with our fully responsive mobile experience.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FLEET SECTION ===== -->
<section class="fleet-section py-5" id="fleet">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Our Fleet</span>
      <h2>Premium Vehicles,<br><span class="gradient-text">Exceptional Experience</span></h2>
    </div>
    <div class="row g-4">
      <?php foreach ($featuredVehicles as $v): ?>
      <div class="col-md-6 col-lg-4">
        <div class="vehicle-card glass-card">
          <div class="vehicle-img-wrap">
            <?php
            $imgPath = 'assets/images/vehicles/' . $v['image'];
            $imgUrl = (file_exists($imgPath) && $v['image'] !== 'default.jpg') ? $imgPath : 'assets/images/car-placeholder.svg';
            ?>
            <img src="<?= $imgUrl ?>" alt="<?= clean($v['vehicle_name']) ?>" class="vehicle-img">
            <span class="vehicle-type-badge"><?= clean($v['type_name'] ?? 'Vehicle') ?></span>
            <span class="vehicle-status-badge status-<?= strtolower($v['status']) ?>"><?= $v['status'] ?></span>
          </div>
          <div class="vehicle-card-body">
            <h5><?= clean($v['vehicle_name']) ?></h5>
            <p class="vehicle-brand"><i class="bi bi-building me-1"></i><?= clean($v['brand']) ?> · <?= $v['year'] ?></p>
            <div class="vehicle-specs d-flex gap-3">
              <span><i class="bi bi-people-fill me-1"></i><?= $v['seats'] ?> seats</span>
              <span><i class="bi bi-fuel-pump me-1"></i><?= $v['fuel_type'] ?></span>
              <span><i class="bi bi-gear me-1"></i><?= $v['transmission'] ?></span>
            </div>
            <div class="vehicle-card-footer mt-3 d-flex justify-content-between align-items-center">
              <div>
                <span class="price-amount"><?= formatCurrency($v['price_per_day']) ?></span>
                <span class="price-unit">/day</span>
              </div>
              <a href="register.php" class="btn btn-primary-glow btn-sm">Book Now</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="register.php" class="btn btn-glass btn-lg px-5">
        <i class="bi bi-grid me-2"></i>View All Vehicles
      </a>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-section py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Simple Process</span>
      <h2>Rent a Car in<br><span class="gradient-text">3 Simple Steps</span></h2>
    </div>
    <div class="row g-4 align-items-center">
      <div class="col-md-4">
        <div class="step-card glass-card text-center">
          <div class="step-number">01</div>
          <div class="step-icon"><i class="bi bi-person-plus-fill"></i></div>
          <h4>Create Account</h4>
          <p>Register for free in 30 seconds. No credit card required to sign up.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card glass-card text-center">
          <div class="step-number">02</div>
          <div class="step-icon"><i class="bi bi-search"></i></div>
          <h4>Choose Vehicle</h4>
          <p>Browse our premium fleet, filter by type, budget, and availability.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card glass-card text-center">
          <div class="step-number">03</div>
          <div class="step-icon"><i class="bi bi-car-front-fill"></i></div>
          <h4>Drive & Enjoy</h4>
          <p>Get instant confirmation and pick up your vehicle. It's that simple.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== REVIEWS SECTION ===== -->
<?php if (!empty($reviews)): ?>
<section class="reviews-section py-5" id="reviews">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Testimonials</span>
      <h2>What Our Customers<br><span class="gradient-text">Say About Us</span></h2>
    </div>
    <div class="row g-4">
      <?php foreach ($reviews as $r): ?>
      <div class="col-md-6 col-lg-4">
        <div class="review-card glass-card">
          <div class="review-stars mb-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?> text-warning"></i>
            <?php endfor; ?>
          </div>
          <p class="review-text">"<?= clean($r['message']) ?>"</p>
          <div class="reviewer d-flex align-items-center gap-3 mt-3">
            <div class="reviewer-avatar"><?= strtoupper(substr($r['full_name'], 0, 1)) ?></div>
            <div>
              <strong><?= clean($r['full_name']) ?></strong>
              <?php if ($r['vehicle_name']): ?>
              <small class="d-block text-muted">Rented <?= clean($r['vehicle_name']) ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section py-5">
  <div class="container">
    <div class="cta-card glass-card text-center py-5">
      <h2 class="mb-3">Ready to Hit the Road?</h2>
      <p class="mb-4 text-muted">Join thousands of satisfied customers who trust DriveFlow for all their rental needs.</p>
      <a href="register.php" class="btn btn-primary-glow btn-lg px-5">
        <i class="bi bi-rocket-takeoff me-2"></i>Get Started Free
      </a>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="site-footer py-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-4">
        <a class="navbar-brand" href="index.php">
          <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
          Drive<span class="brand-accent">Flow</span>
        </a>
        <p class="mt-2 text-muted small">Pakistan's premium vehicle rental & fleet management platform.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="footer-links">
          <a href="login.php">Login</a>
          <a href="register.php">Register</a>
          <a href="#fleet">Fleet</a>
          <a href="#reviews">Reviews</a>
        </div>
      </div>
      <div class="col-md-4 text-md-end">
        <p class="text-muted small mb-0">&copy; <?= date('Y') ?> DriveFlow. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>