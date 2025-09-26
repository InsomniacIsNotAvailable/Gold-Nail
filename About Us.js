
        // Simple animation observer
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.team-member, .about-image img, .testimonial').forEach(el => {
                observer.observe(el);
                el.classList.add('fade-in');
            });
            
            // Simple testimonial slider functionality
            const testimonials = [
                {
                    text: "The engagement ring Luxe created for us was beyond anything I could have imagined. The attention to detail and personal service made the entire experience unforgettable.",
                    author: "Sarah Mitchell",
                    role: "Happy Customer",
                    img: "https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/58f9fd46-3532-425b-b20c-3692448bb251.png"
                },
                {
                    text: "Working with Luxe for our anniversary jewelry was a pleasure. They understood exactly what we wanted and delivered a piece that exceeded our expectations.",
                    author: "James Wilson",
                    role: "Satisfied Client",
                    img: "https://placehold.co/100x100"
                },
                {
                    text: "The quality of craftsmanship is exceptional. I've purchased multiple pieces over the years and each one feels like a family heirloom in the making.",
                    author: "Emma Rodriguez",
                    role: "Loyal Customer",
                    img: "https://placehold.co/100x100"
                }
            ];
            
            const testimonialSlider = document.querySelector('.testimonial-slider');
            let currentTestimonial = 0;
            
            function showTestimonial(index) {
                const testimonial = testimonials[index];
                testimonialSlider.innerHTML = `
                    <div class="testimonial">
                        <div class="testimonial-content">
                            <p class="testimonial-text">"${testimonial.text}"</p>
                            <div class="testimonial-author">
                                <img src="${testimonial.img}" alt="${testimonial.author}, ${testimonial.role} smiling with their jewelry">
                                <div class="author-info">
                                    <h4>${testimonial.author}</h4>
                                    <p>${testimonial.role}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            showTestimonial(0);
            
            setInterval(() => {
                currentTestimonial = (currentTestimonial + 1) % testimonials.length;
                showTestimonial(currentTestimonial);
            }, 5000);
        });
  // Modal functionality
  const contactBtn = document.getElementById('contactBtn');
  const contactModal = document.getElementById('contactModal');
  const closeModal = document.getElementById('closeModal');

  contactBtn.addEventListener('click', function(e) {
    e.preventDefault();
    contactModal.style.display = 'block';
  });

  closeModal.addEventListener('click', function() {
    contactModal.style.display = 'none';
  });

  window.addEventListener('click', function(e) {
    if (e.target === contactModal) {
      contactModal.style.display = 'none';
    }
  });