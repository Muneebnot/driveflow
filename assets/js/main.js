// ============================================================
// DriveFlow – Main JavaScript
// assets/js/main.js
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── Navbar scroll effect ──
  const navbar = document.getElementById('mainNavbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) navbar.classList.add('scrolled');
      else navbar.classList.remove('scrolled');
    });
  }

  // ── Animate numbers on scroll ──
  function animateCounter(el) {
    const target = parseInt(el.dataset.target) || parseInt(el.textContent);
    if (isNaN(target)) return;
    const duration = 1500;
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = Math.floor(current) + (el.textContent.includes('+') ? '+' : '');
    }, 16);
  }

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('.stat-num, .counter').forEach(el => counterObserver.observe(el));

  // ── Fade in on scroll ──
  const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.glass-card, .feature-card, .vehicle-card, .step-card, .review-card').forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = `opacity 0.6s ease ${i * 0.05}s, transform 0.6s ease ${i * 0.05}s`;
    fadeObserver.observe(el);
  });

  // ── Auto dismiss alerts ──
  document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // ── Tooltips ──
  if (typeof bootstrap !== 'undefined') {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  // ── Flash message from session ──
  const flashMsg = document.querySelector('.flash-alert');
  if (flashMsg) {
    setTimeout(() => {
      flashMsg.style.transition = 'all 0.5s';
      flashMsg.style.opacity = '0';
      flashMsg.style.transform = 'translateY(-20px)';
      setTimeout(() => flashMsg.remove(), 500);
    }, 4000);
  }
});

// ── Admin Sidebar Toggle ──
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  sidebar?.classList.toggle('open');
  overlay?.classList.toggle('show');
}
document.addEventListener('click', e => {
  if (e.target.closest('.sidebar-overlay')) {
    document.querySelector('.sidebar')?.classList.remove('open');
    document.querySelector('.sidebar-overlay')?.classList.remove('show');
  }
});

// ── Delete confirm ──
function confirmDelete(url, msg = 'Are you sure you want to delete this?') {
  if (confirm(msg)) window.location.href = url;
}

// ── Image preview before upload ──
function previewImage(input, imgId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById(imgId);
      if (img) img.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}