<?php
require_once __DIR__ . '/backend/includes/gold_chart_utils.php';
$goldChart = gold_build_chart_params($_GET);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gold Nail - Buy & Sell Gold and Accessories</title>
    <link rel="stylesheet" href="SellGold.css">
    <link rel="stylesheet" href="assets/css/gold_chart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.umd.min.js"></script>
    <script src="backend/package/dist/chartjs-chart-financial.js"></script>
    <script>
      (function registerFinancial() {
        function isRegistered(){return !!(Chart?.registry?.controllers?.get?.('candlestick'));}
        if (isRegistered()){window.__financialReady=Promise.resolve(true);return;}
        window.__financialReady=Promise.resolve().then(()=>{
          try{
            const F=window['chartjs-chart-financial']||{};
            const regs=[];
            ['FinancialElement','FinancialController','CandlestickController','OhlcController'].forEach(k=>{ if(F[k]) regs.push(F[k]); });
            if(regs.length) Chart.register(...regs);
          }catch(e){console.warn('[financial] manual register failed',e);}
          return isRegistered();
        });
        try {
          const Zoom=window['chartjs-plugin-zoom'];
          if(Zoom) Chart.register(Zoom);
        } catch(e){ console.warn('[zoom] registration error',e); }
      })();
    </script>
    <script src="backend/scripts/charts/gold_candles_chartjs_config.js"></script>
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
          <a href="Homepage.php" class="nav-link">Home</a>
          <a href="SellGold.php" class="nav-link current-page">Gold Prices</a>
          <a href="About Us.php" class="nav-link">About Us</a>
        </nav>
      </div>
    </header>

    <section class="hero">
        <h1>Your Trusted Gold Partner</h1>
        <p>Get the best value for your gold and browse our exquisite collection of gold accessories</p>
        <div>
            <a href="#services" class="btn">Our Services</a>
            <a href="#sell-gold-process" class="btn" style="margin-left: 1rem; background-color: white; color: var(--dark);">Sell Your Gold</a>
        </div>
    </section>

    <section id="live-gold-chart" class="gold-chart-section">
      <div class="gold-chart-shell">
        <h2>Live Gold Price (Candlestick)</h2>
        <div class="gold-chart-controls">
          <form id="goldChartForm" method="get" action="SellGold.php">
            <div class="left">
              <label>
                Month
                <input type="month" name="month" value="<?= htmlspecialchars($goldChart['month'], ENT_QUOTES) ?>" />
              </label>
              <label>
                Karat
                <select name="karat">
                  <?php foreach (['24','22','21','18','14','10'] as $k): ?>
                    <option value="<?= $k ?>" <?= $goldChart['karat'] === $k ? 'selected' : '' ?>><?= $k ?>K</option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Redraw</button>
              <a class="button-link" href="SellGold.php?month=<?= date('Y-m') ?>&karat=<?= htmlspecialchars($goldChart['karat'], ENT_QUOTES) ?>">Current</a>
            </div>
            <div class="right">
              <button type="button" id="goldGoLatest" title="Center latest candle">Go Latest</button>
              <button type="button" id="goldResetZoom" title="Reset zoom/pan">Reset Zoom</button>
            </div>
          </form>
        </div>
        <div class="gold-chart-stage">
          <canvas id="goldChartCanvas"></canvas>
          <div id="goldChartEmpty" class="gold-chart-empty">Loading...</div>
          <div class="gold-chart-pan-tip">Pan: Shift + drag. Zoom: wheel / pinch.</div>
        </div>
        <p class="chart-footnote">
          Prices in PHP/gram adjusted by karat purity. Auto daily sync enabled.
        </p>
      </div>
      <script id="goldChartBootstrap" type="application/json"><?= json_encode($goldChart, JSON_UNESCAPED_SLASHES) ?></script>
    </section>

    <section id="services" class="section-padding">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services">
                <div class="service-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/9b124b5a-0aaf-4294-b22a-a59bb8590bb2.png" alt="Gold buyer inspecting jewelry with magnifying glass in professional setting">
                    <div class="service-content">
                        <h3>Gold Buying</h3>
                        <p>We offer competitive rates for your gold items with instant payment. Our experts assess purity and weight for best pricing.</p>
                        <a href="#sell-gold-process" class="btn">Learn More</a>
                    </div>
                </div>
                <div class="service-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2c7a12ec-9e3f-4e0e-8315-446e4390a82e.png" alt="Gold coins and bars displayed on velvet cloth under warm lighting">
                    <div class="service-content">
                        <h3>Gold Selling</h3>
                        <p>Purchase certified gold accessories and nuggets at market prices with purity certificates.</p>
                        <a href="#accessories" class="btn">Learn More</a>
                    </div>
                </div>
                <div class="service-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-862f1-2a93ccde5887/image/d60212f2-a1f6-4b2c-912c-44cf67eecd10.png" alt="Collection of gold jewelry including necklaces, rings and bracelets on display">
                    <div class="service-content">
                        <h3>Gold Services</h3>
                        <p>Handling and care services for your gold pieces and accessories.</p>
                        <a href="About Us.html#services-section" class="btn">View Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="sell-gold-process" class="section-padding">
        <div class="container">
            <h2 class="section-title">How To Sell Your Gold</h2>
            <div class="process-steps">
                <div class="step">
                    <h3>Schedule an Appointment</h3>
                    <p>Book a convenient time using our online scheduling.</p>
                </div>
                <div class="step">
                    <h3>Professional Evaluation</h3>
                    <p>We assess purity and weight with precise instruments (2–5 minutes).</p>
                </div>
                <div class="step">
                    <h3>Receive Offer</h3>
                    <p>Transparent cash offer based on current market and purity.</p>
                </div>
                <div class="step">
                    <h3>Get Paid</h3>
                    <p>Immediate payment (cash, transfer, check, or 10% bonus store credit).</p>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="section-padding" style="background-color: var(--light-gold);">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq">
                <div class="faq-item">
                    <div class="faq-question">How do you determine the value of my gold? <i class="fas fa-chevron-down faq-icon"></i></div>
                    <div class="faq-answer">
                        <p>We evaluate market price, purity (karat), and weight using calibrated tools.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">What types of gold do you buy? <i class="fas fa-chevron-down faq-icon"></i></div>
                    <div class="faq-answer">
                        <p>Jewelry, coins, dental gold, and scrap from 10K to 24K.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Do I need an appointment? <i class="fas fa-chevron-down faq-icon"></i></div>
                    <div class="faq-answer">
                        <p>Walk-ins welcome; appointments reduce wait time.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">How do I get paid? <i class="fas fa-chevron-down faq-icon"></i></div>
                    <div class="faq-answer">
                        <p>Cash, transfer, check, or store credit (10% bonus).</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="accessories" class="section-padding">
        <div class="container">
            <h2 class="section-title">Our Exquisite Gold Accessories</h2>
            <div class="accessories-grid">
                <div class="accessory-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/fb828a2b-102e-4861-829d-4767e7c858be.png" alt="Elegant gold necklace with a intricate pendant">
                    <h3>Necklaces</h3>
                    <p>Delicate chains to bold statement pieces.</p>
                </div>
                <div class="accessory-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/48592965-02fb-4841-8f55-73895a9e339d.png" alt="Gold hoop earrings">
                    <h3>Earrings</h3>
                    <p>Hoops, studs, and dangles for any occasion.</p>
                </div>
                <div class="accessory-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ed829871-edb0-4ecb-93ff-153314cf2c59.png" alt="Gold wedding band">
                    <h3>Rings</h3>
                    <p>Timeless bands and standout cocktail designs.</p>
                </div>
                <div class="accessory-card">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/41a13e59-1994-436f-b145-29654516cefb.png" alt="Gold bracelet links">
                    <h3>Bracelets</h3>
                    <p>Classic and modern designs in multiple karats.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Replaced form section with single CTA button -->
    <section id="appointment" class="section-padding" style="background-color: var(--light-gold);">
        <div class="container" style="text-align:center; max-width:820px;">
            <h2 class="section-title" style="margin-bottom:1rem;">
                <i class="fas fa-calendar-check" style="color:var(--gold); margin-right:10px;"></i>
                Book an Appointment & Get a Gold Quote
            </h2>
            <p style="color:#444; font-size:1.05rem; line-height:1.5; margin:0 0 1.75rem;">
                Ready to get a professional gold evaluation or purchase certified pieces? Schedule a visit and our team will assist you with transparent pricing, purity assessment, and instant payment options.
            </p>
            <a href="appointment_form.php" class="btn btn-gold-calc" style="font-size:1.05rem; padding:14px 30px; display:inline-flex; align-items:center; gap:8px;">
              <i class="fas fa-arrow-right"></i>
              Proceed to Appointment Form
            </a>
            <div style="margin-top:2rem; font-size:0.85rem; color:#555;">
              <i class="fas fa-lock"></i> Your information is kept private and secure.
            </div>
        </div>
    </section>

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
                <div class="footer-column" id="contact-info">
                    <h3>Contact</h3>
                    <ul>
                        <li>4740 la villa III unit 104 solchuaga street brgy tejeros makati city</li>
                        <li>Landline: 83625478</li>
                        <li>Phone Number: +639490561676 </li>
                        <li>Email: info@goldnail.com</li>
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

    <script type="module" src="backend/scripts/gold/index.js"></script>
    <script src="backend/scripts/backfill.js"></script>
    <script src="SellGold.js"></script>
</body>
</html>