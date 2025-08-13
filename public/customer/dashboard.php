<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_role(['customer']);
include __DIR__ . '/../includes/header.php';
?>
  <section class="hero">
    <div class="hero-content">
      <h1>Book Your Dorm Room Easily</h1>
      <p class="subtitle">Secure and hassle-free dormitory reservations for students.</p>
    </div>
  </section>
  <div class="cta-center">
    <a class="btn btn-large accent" href="/customer/my_reservations.php">View My Reservations</a>
  </div>
  <div class="promo full-bleed">
    <p>"Dorm hunting made simple. From comfy beds to secure facilities, we’ve got you covered."</p>
  </div>

  <section class="features">
    <h2>Why Choose Us?</h2>
    <div class="features-grid">
      <div class="feature-card">
        <img src="/asset/1.png" alt="Comfy rooms" />
        <div class="content">
          <p class="title">Comfy</p>
          <p class="desc">Spacious, well-ventilated rooms designed for your comfort</p>
        </div>
      </div>
      <div class="feature-card">
        <img src="/asset/2.png" alt="Clean facilities" />
        <div class="content">
          <p class="title">Clean</p>
          <p class="desc">Regularly maintained dorms to ensure a hygienic environment</p>
        </div>
      </div>
      <div class="feature-card">
        <img src="/asset/3.png" alt="Secure environment" />
        <div class="content">
          <p class="title">Secure</p>
          <p class="desc">Security and controlled access to keep you safe</p>
        </div>
      </div>
      <div class="feature-card">
        <img src="/asset/4.png" alt="Easy booking" />
        <div class="content">
          <p class="title">Easy Booking</p>
          <p class="desc">Reserve your room online in just a few clicks</p>
        </div>
      </div>
      <div class="feature-card">
        <img src="/asset/5.png" alt="Great location" />
        <div class="content">
          <p class="title">Great Location</p>
          <p class="desc">Close to universities, transportation, and key amenities</p>
        </div>
      </div>
      <div class="feature-card">
        <img src="/asset/6.png" alt="Free Wi-Fi" />
        <div class="content">
          <p class="title">Free Wi-Fi</p>
          <p class="desc">Stay connected anytime with our high-speed internet.</p>
        </div>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>


