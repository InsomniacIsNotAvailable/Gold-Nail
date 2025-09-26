<?php
require_once __DIR__ . '/backend/lib/ip_logger.php';
$ipLog = log_request_ip('home');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gold Nail in Your Area</title>
  <link rel="stylesheet" href="Homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
  <div class="announcement-bar" role="region" aria-label="Announcement">
    We ensure safe and secure transactions for your items
  </div>
  <header>
    <div class="container header-content-padding flex justify-between items-center">
      <div class="flex items-center">
        <span class="icon-gem" aria-hidden="true"></span>
        <span class="gold-text">Gold Nail</span>
      </div>
      <nav>
        <!-- Updated to PHP targets -->
        <a href="Homepage.php" class="nav-link current-page">Home</a>
        <a href="SellGold.php" class="nav-link">Gold Prices</a>
        <a href="AboutUs.php" class="nav-link">About Us</a>
      </nav>
    </div>
  </header>
  <main>
    <section id="home" class="hero-gradient">
      <div class="hero-content flex items-center">
        <div style="flex:1;">
          <div class="hero-title">Get the Best Value for Your Gold</div>
            <div class="hero-desc">We offer competitive rates and professional evaluation for your gold items. Trusted by thousands of customers across the country.</div>
          <div class="hero-btns">
            <a href="#contact" class="btn btn-outline">Contact Us</a>
          </div>
        </div>
        <div style="flex:1; display:flex; justify-content:center;">
          <img id="hero-img"
            src="https://images.unsplash.com/photo-1612033620959-1e3d3b7e7b7e?q=80&w=1170&auto=format&fit=crop"
            alt="Close-up of various gold jewelry pieces, including rings, necklaces, and bracelets."
            class="hero-img">
        </div>
      </div>
    </section>

    <section id="why-choose-us" class="container">
      <div class="section-title">Why Choose Us?</div>
      <div class="flex">
        <div class="reason-card">
          <div class="card-icon" aria-hidden="true">&#9733;</div>
          <h3 class="card-title">Competitive Rates</h3>
          <p class="card-description">We offer the best market rates for your gold, ensuring you get maximum value.</p>
        </div>
        <div class="reason-card">
          <div class="card-icon" aria-hidden="true">&#128274;</div>
          <h3 class="card-title">Secure Transactions</h3>
          <p class="card-description">Your safety is our priority with fully secure and transparent transactions.</p>
        </div>
        <div class="reason-card">
          <div class="card-icon" aria-hidden="true">&#128279;</div>
          <h3 class="card-title">Trustable Partners</h3>
          <p class="card-description">Collaborating with reputable partners, we guarantee transparency and quality in every transaction.</p>
        </div>
      </div>
    </section>

    <section id="contact">
      <div class="container">
        <div class="section-title">Contact Us</div>
        <form id="contactForm" class="contact-form" method="POST" action="admin.php">
          <label class="form-label" for="name">Name</label>
          <input type="text" id="name" name="name" class="form-input" required>
          <label class="form-label" for="number">Number</label>
          <input type="tel" id="number" name="number" class="form-input" required>
            <label class="form-label" for="message">Message</label>
          <textarea id="message" name="message" class="form-input" required></textarea>
          <button type="submit" class="btn btn-gold" style="width:100%;">Send Message</button>
        </form>
      </div>
    </section>
  </main>
  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-column">
          <h3>Gold Nail</h3>
          <p>Your trusted partner for gold transactions and accessories since 2010.</p>
        </div>
        <div class="footer-column">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="Homepage.php">Home</a></li>
            <li><a href="SellGold.php">Sell Gold</a></li>
            <li><a href="AboutUs.php">About Us</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h3>Contact</h3>
          <ul>
            <li>4740 la villa III unit 104 solchuaga street brgy tejeros makati city</li>
            <li>Landline: 83625478</li>
            <li>Phone Number: +639490561676</li>
          </ul>
        </div>
        <div class="footer-column">
          <h3>Hours</h3>
          <ul>
            <li>Daily: 7am-8pm</li>
          </ul>
        </div>
      </div>
      <div class="copyright">
        <p>© 2025 Gold Nail. All rights reserved.</p>
      </div>
    </div>
  </footer>
  <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">
    <i class="fas fa-moon"></i>
  </button>
  <script src="Homepage.js"></script>
  <script src="backend/scripts/goldAdmin.js"></script>
  <script src="backend/scripts/email/testEmailHelper.js"></script>
</body>
</html>