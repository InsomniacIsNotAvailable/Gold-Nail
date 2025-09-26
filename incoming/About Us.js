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
            text: "The engagement ring Gold Nail created for us was beyond anything I could have imagined. The attention to detail and personal service made the entire experience unforgettable.",
            author: "Sarah Mitchell",
            role: "Happy Customer",
            img: "https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/58f9fd46-3532-425b-b20c-3692448bb251.png"
        },
        {
            text: "Working with Gold Nail for our anniversary jewelry was a pleasure. They understood exactly what we wanted and delivered a piece that exceeded our expectations.",
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

    // Fix: Site message close button
    const closeSiteMsg = document.getElementById('close-site-message');
    if (closeSiteMsg) {
        closeSiteMsg.onclick = function() {
            const siteMsg = document.getElementById('site-message');
            if (siteMsg) siteMsg.style.display = 'none';
        };
    }

    // Modal functionality
    const contactBtn = document.getElementById('contactBtn');
    const contactModal = document.getElementById('contactModal');
    const closeModal = document.getElementById('closeModal');

    if (contactBtn && contactModal && closeModal) {
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
    }

    // Header background carousel
    const header = document.getElementById('about-header') || document.querySelector('header');
    const headerImages = [
        "https://images.unsplash.com/photo-1709295208567-ce817387e977?w=1200&auto=format&fit=crop&q=80&ixlib=rb-4.1.0",
        "https://images.unsplash.com/photo-1617117811969-97f441511dee?w=1200&auto=format&fit=crop&q=80&ixlib=rb-4.1.0",
        "https://images.unsplash.com/photo-1717409014701-8e630ff057f3?q=1200&w=1334&auto=format&fit=crop&ixlib=rb-4.1.0"
    ];
    let headerIdx = 0;
    if (header) {
        // Set initial background
        header.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('${headerImages[0]}')`;
        header.style.backgroundSize = "cover";
        header.style.backgroundPosition = "center";
        setInterval(() => {
            headerIdx = (headerIdx + 1) % headerImages.length;
            header.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('${headerImages[headerIdx]}')`;
        }, 2500);
    }
});