

  // --- Homepage Contact Form Functionality ---
  const contactForm = document.getElementById('contactForm');
  const formResponse = document.getElementById('form-response');
  const closeFormResponse = document.getElementById('close-form-response');
  const formError = document.getElementById('form-error');
  const closeFormError = document.getElementById('close-form-error');
  if (contactForm) {
    contactForm.addEventListener('submit', function(event) {
      event.preventDefault();
      const name = document.getElementById('name').value.trim();
      const number = document.getElementById('number').value.trim();
      const message = document.getElementById('message').value.trim();
      if (!name || !number || !message) {
        if (formError) formError.style.display = 'block';
        if (formResponse) formResponse.style.display = 'none';
        return;
      }
      if (formError) formError.style.display = 'none';
      if (formResponse) formResponse.style.display = 'block';
      contactForm.reset();
    });
  }
  if (closeFormResponse) {
    closeFormResponse.onclick = function() {
      formResponse.style.display = 'none';
    };
  }
  if (closeFormError) {
    closeFormError.onclick = function() {
      formError.style.display = 'none';
    };
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
    body.classList.toggle('dark-mode', isDarkMode);
    if (darkModeToggle) {
      const icon = darkModeToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-moon', !isDarkMode);
        icon.classList.toggle('fa-sun', isDarkMode);
      }
    }
  };

  // Check for saved theme preference on load
  const savedTheme = localStorage.getItem('theme');
  applyTheme(savedTheme === 'dark');

  // Add event listener for the toggle button
  if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
      const isDarkMode = !body.classList.contains('dark-mode');
      applyTheme(isDarkMode);
      localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    });
  }
