<?php
// PHP code here
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gold Nail in Your Area</title>
  <link rel="stylesheet" href="Homepage.css">
  <link rel="stylesheet" href="chat.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <!-- Announcement bar - Consistent across pages -->
  <div class="announcement-bar" role="region" aria-label="Announcement">
    We ensure safe and secure transactions for your items
  </div>
  <header>
    <div class="container header-content-padding flex justify-between items-center" style="padding:1.2rem 1rem;">
      <div class="flex items-center">
        <img src="IMG/N.png" alt="Logo of Gold Nail" style="width:40px; height:40px; border-radius:20%; margin-right:10px;">
        <span class="gold-text">Gold Nail</span>
      </div>
      <nav style="display:flex;">
        <a href="Homepage.php" class="nav-link current-page">Home</a>
        <a href="Sell Gold.php" class="nav-link">Gold Prices</a>
        <a href="About Us.php" class="nav-link">About Us</a>
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
            <!-- Linking to the contact section on the same page -->
            <a href="#contact" class="btn btn-outline">Contact Us</a>
          </div>
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
        <!-- Success message (hidden by default) -->
        <div id="form-response" style="display:none; margin-bottom:1rem; padding:1rem; border-radius:6px; background:#e6ffd8; color:#256029; font-weight:600; text-align:center; border:1px solid #b6e6a7;">
          Thank you for your message! We will get back to you soon.
          <span id="close-form-response" style="margin-left:20px; cursor:pointer; font-weight:bold;">&times;</span>
        </div>
        <!-- Error message (hidden by default) -->
        <div id="form-error" style="display:none; margin-bottom:1rem; padding:1rem; border-radius:6px; background:#ffeaea; color:#a94442; font-weight:600; text-align:center; border:1px solid #f5c6cb;">
          Please fill in all fields.
          <span id="close-form-error" style="margin-left:20px; cursor:pointer; font-weight:bold;">&times;</span>
        </div>
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
                    <li><a href="Sell Gold.php">Sell Gold</a></li>
                    <li><a href="About Us.php">About Us</a></li>
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
  <!-- Dark Mode Toggle Button - Now floating at bottom right -->
  <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">
    <i class="fas fa-moon"></i>
  </button>

  <div id="chatbot-icon" title="Chat with us!"></div>

  <!-- Chat Container -->
  <div id="chat-container" class="hidden">
      <div class="chat-header">
          <div class="chat-title">Gold Nail Support</div>
          <button class="close-chat">&times;</button>
      </div>
      <div id="chat-box">
          <div id="chat-history">
              <div class="welcome-message">
                  👋 Hello! I'm here to help you with any questions about Gold Nail's services, gold prices, or appointments. How can I assist you today?
              </div>
              <div class="quick-actions">
                  <button class="quick-action-btn" data-message="What are your current gold prices?">Gold Prices</button>
                  <button class="quick-action-btn" data-message="How can I sell my gold?">Sell Gold</button>
                  <button class="quick-action-btn" data-message="What are your business hours?">Hours</button>
                  <button class="quick-action-btn" data-message="Where are you located?">Location</button>
              </div>
          </div>
          <div class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
          </div>
      </div>
      <div id="chat-input-box">
          <input type="text" id="chat-input" placeholder="Type your message here..." maxlength="500">
          <button id="send-button">Send</button>
      </div>
  </div>
  <script src="Homepage.js"></script>
  <script src="chat.js"></script>
</body>
</html>