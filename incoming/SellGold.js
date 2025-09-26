document.addEventListener('DOMContentLoaded', function () {
  // FAQ functionality
  const faqQuestions = document.querySelectorAll('.faq-question');

  faqQuestions.forEach(question => {
    question.addEventListener('click', () => {
      const answer = question.nextElementSibling;
      const isActive = answer.classList.contains('active');

      // Close all answers first
      document.querySelectorAll('.faq-answer').forEach(item => {
        item.classList.remove('active');
      });
      // Remove active class from all questions
      document.querySelectorAll('.faq-question').forEach(item => {
        item.classList.remove('active');
      });


      // Open clicked one if it wasn't already active
      if (!isActive) {
        answer.classList.add('active');
        question.classList.add('active'); // Add active class to the question too
      }
    });
  });

  // Smooth scrolling for navigation links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();

      const targetId = this.getAttribute('href');
      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80, // Adjust for sticky header height
          behavior: 'smooth'
        });
      }
    });
  });

  // Animate elements when they come into view
  const animateOnScroll = function () {
    const elements = document.querySelectorAll('.service-card, .step, .accessory-card'); // Removed .calculator-card

    elements.forEach(element => {
      const elementPosition = element.getBoundingClientRect().top;
      const viewportHeight = window.innerHeight;

      // Trigger animation when element is 100px from the bottom of the viewport
      if (elementPosition < viewportHeight - 100) {
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
      }
    });
  };

  // Set initial state for animated elements
  document.querySelectorAll('.service-card, .step, .accessory-card').forEach(element => { // Removed .calculator-card
    element.style.opacity = '0';
    element.style.transform = 'translateY(30px)';
    element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
  });

  window.addEventListener('scroll', animateOnScroll);
  animateOnScroll(); // Run once on load to animate elements already in view

  // --- Dark Mode Toggle Functionality ---
  const darkModeToggle = document.getElementById('darkModeToggle');
  const body = document.body;

  // Function to apply the theme
  const applyTheme = (isDarkMode) => {
    body.classList.toggle('dark-mode', isDarkMode);
    if (darkModeToggle) {
      const icon = darkModeToggle.querySelector('i');
      if (icon) {
        icon.classList.remove('fa-moon', 'fa-sun');
        icon.classList.add(isDarkMode ? 'fa-sun' : 'fa-moon');
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
});