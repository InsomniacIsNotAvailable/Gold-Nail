document.addEventListener('DOMContentLoaded', function() {
  // Hero image slider
  const images = [
    "https://images.unsplash.com/photo-1709295208567-ce817387e977?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0",
    "https://images.unsplash.com/photo-1617117811969-97f441511dee?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0",
    "https://images.unsplash.com/photo-1717409014701-8e630ff057f3?q=120&w=1334&auto=format&fit=crop&ixlib=rb-4.1.0"
  ];
  let idx = 0;
  const img = document.getElementById('hero-img');

  // Set initial opacity to 1 after the first image loads to prevent FOUC
  if (img) {
    img.onload = () => {
      img.style.opacity = 1;
    };
    // If image is already cached, ensure it becomes visible
    if (img.complete) {
      img.style.opacity = 1;
    }
  }

  // Auto-slide functionality
  setInterval(() => {
    if (img) {
      img.style.opacity = 0; // Fade out current image
      setTimeout(() => {
        idx = (idx + 1) % images.length;
        img.src = images[idx]; // Change image source
        img.style.opacity = 1; // Fade in new image
      }, 500); // Wait for fade out to complete before changing src
    }
  }, 4000); // Change image every 4 seconds

  // --- Homepage Contact Form Functionality ---
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function(event) {
      event.preventDefault(); // Prevent default form submission (page reload)

      // Get form data
      const name = document.getElementById('name').value;
      const number = document.getElementById('number').value;
      const message = document.getElementById('message').value;

      console.log('Contact Form Submission:');
      console.log('Name:', name);
      console.log('Number:', number);
      console.log('Message:', message);

      alert('Thank you for your message! We will get back to you soon.');
      contactForm.reset(); // Clear the form fields after submission
    });
  }

  // --- Scroll-based Announcement Bar Visibility and Header Position ---
  const announcementBar = document.querySelector('.announcement-bar');
  const header = document.querySelector('header');
  let announcementBarHeight = announcementBar ? announcementBar.offsetHeight : 0; // Get initial height

  const updateScrollBehavior = () => {
    if (!announcementBar || !header) return; // Exit if elements not found

    if (window.scrollY > announcementBarHeight) {
      // Scrolled past the initial height of the announcement bar
      announcementBar.classList.add('hidden');
      header.style.top = '0px'; // Header moves to the top
    } else {
      // Scrolled back to the top or within the announcement bar's original height
      announcementBar.classList.remove('hidden');
      // Header stays below the announcement bar's original height
      header.style.top = announcementBarHeight + 'px';
    }
  };

  // Set initial header position and announcement bar visibility on load
  // Ensure the header starts below the announcement bar
  if (header && announcementBar) {
    header.style.top = announcementBarHeight + 'px';
  }
  updateScrollBehavior(); // Call once on load to set initial state

  // Add scroll event listener
  window.addEventListener('scroll', updateScrollBehavior);


  // Smooth scrolling for navigation links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();

      const targetId = this.getAttribute('href');
      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        // Recalculate offset dynamically
        const headerHeight = header ? header.offsetHeight : 0; // Get current header height
        // If announcement bar is hidden, its effective height for offset is 0
        const currentAnnouncementOffset = announcementBar && announcementBar.classList.contains('hidden') ? 0 : announcementBarHeight;
        const totalOffset = currentAnnouncementOffset + headerHeight + 20; // Added extra padding

        window.scrollTo({
          top: targetElement.offsetTop - totalOffset,
          behavior: 'smooth'
        });
      }
    });
  });

  // --- Dark Mode Toggle Functionality ---
  const darkModeToggle = document.getElementById('darkModeToggle');
  const body = document.body;

  // Function to apply the theme
  const applyTheme = (isDarkMode) => {
    if (isDarkMode) {
      body.classList.add('dark-mode');
      if (darkModeToggle) {
        darkModeToggle.querySelector('i').classList.remove('fa-moon');
        darkModeToggle.querySelector('i').classList.add('fa-sun');
      }
    } else {
      body.classList.remove('dark-mode');
      if (darkModeToggle) {
        darkModeToggle.querySelector('i').classList.remove('fa-sun');
        darkModeToggle.querySelector('i').classList.add('fa-moon');
      }
    }
  };

  // Check for saved theme preference on load
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    applyTheme(true);
  } else {
    applyTheme(false); // Apply light theme by default or if preference is not dark
  }

  // Add event listener for the toggle button
  if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
      const isDarkMode = body.classList.contains('dark-mode');
      applyTheme(!isDarkMode); // Toggle theme
      localStorage.setItem('theme', !isDarkMode ? 'dark' : 'light'); // Save preference
    });
  }
});