<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Gold Nail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="About Us.css">
</head>
<body>
    <!-- Site-wide notification bar -->
    <div id="site-message" style="background: #ffd700; color: #222; padding: 12px 0; text-align: center; font-weight: bold; position: relative;">
        Welcome to Gold Nail! Enjoy our trusted gold services since 2010.
        <span id="close-site-message" style="position:absolute; right:20px; top:0; cursor:pointer; font-size:20px;">&times;</span>
    </div>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal">
      <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Contact Us</h2>
        <form class="contact-form">
          <label for="modal-name">Name</label>
          <input type="text" id="modal-name" class="form-input" required>
          <label for="modal-email">Email</label>
          <input type="email" id="modal-email" class="form-input" required>
          <label for="modal-message">Message</label>
          <textarea id="modal-message" class="form-input" required></textarea>
          <button type="submit" class="btn btn-gold" style="width:100%;">Send Message</button>
        </form>
      </div>
    </div>
    <header id="about-header" data-index="0">
        <!-- Back Button (top left, absolute position, arrow only) -->
        <button onclick="window.history.back()" class="back-btn back-btn-top" aria-label="Back">
            <i class="fas fa-arrow-left"></i>
        </button>
        <img src="IMG\N.png" alt="Logo of Gold Nail">
        <h1 class="fade-in">Our Story</h1>
        <p class="fade-in delay-1">Discover the passion and effort in each services</p>
        <a href="#contact" class="btn fade-in delay-2" id="contactBtn">Contact Us</a>
    </header>

    <main class="container">
        <section class="about-section">
            <div class="section-title">
                <h2>About Gold Nail</h2>
            </div>
            
            <div class="about-content">
                <div class="about-text">
                    <h3>Been at your service since 2010</h3>
                    <p>Founded by Benhor Villosa, Gold Nail started as a small shop on the corner of Pasong Tirad in Tejeros, Makati. What started as a passion project quickly grew into an internationally recognized brand known for its exquisite craftsmanship and ethical sourcing practices.</p>
                    <p>Each piece in our collection tells a story - from ethically sourced gems to recycled precious metals, we combine traditional techniques with contemporary design to create jewelry that stands the test of time.</p>
                    <p>Our dedicated team of artisans brings decades of combined experience to every creation, ensuring quality you can see and feel in every detail.</p>
                </div>
                <div class="about-image">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0f77d573-02a5-448d-8276-76dbca908271.png" alt="Artisan jeweler working at a workshop bench with precision tools and scattered gemstones under warm lighting">
                </div>
            </div>
        </section>

        <section class="team">
            <div class="section-title">
                <h2>Meet the Owners</h2>
            </div>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-info">
                        <h3>Benhor Villosa</h3>
                        <p>Founder</p>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-info">
                        <h3>Bench Joseph Villosa</h3>
                        <p>Co-Owner/Manager</p>
                    </div>
            </div>
        </section>

        <section class="testimonials">
            <div class="section-title">
                <h2>What Our Clients Say</h2>
            </div>
            
            <div class="testimonial-slider">
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p class="testimonial-text">"The engagement ring Luxe created for us was beyond anything I could have imagined. The attention to detail and personal service made the entire experience unforgettable."</p>
                        <div class="testimonial-author">
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/8180c89e-7650-4bb5-ad4f-b82313c1cdfb.png" alt="Sarah Mitchell, happy customer smiling with her engagement ring">
                            <div class="author-info">
                                <h4>Sarah Mitchell</h4>
                                <p>Happy Customer</p>
                            </div>
                        </div>
                    </div>
                </div>
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
    <script src="About US.js"></script>
</body>
</html>

