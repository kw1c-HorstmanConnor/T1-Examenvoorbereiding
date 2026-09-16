(() => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setupGallery() {
        const gallery = document.querySelector('[data-surroundings-gallery]');

        if (!gallery) {
            return;
        }

        const slides = [...gallery.querySelectorAll('[data-slide]')];
        const previousButton = gallery.querySelector('[data-gallery-prev]');
        const nextButton = gallery.querySelector('[data-gallery-next]');
        const dotsWrap = gallery.querySelector('[data-gallery-dots]');
        let currentIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
        let autoplayId = null;

        if (slides.length === 0) {
            return;
        }

        if (currentIndex < 0) {
            currentIndex = 0;
        }

        const dots = slides.map((slide, index) => {
            const button = document.createElement('button');
            button.className = 'surroundings-gallery__dot';
            button.type = 'button';
            button.setAttribute('aria-label', `Show slide ${index + 1}`);
            button.addEventListener('click', () => {
                showSlide(index);
                restartAutoplay();
            });
            dotsWrap?.appendChild(button);
            return button;
        });

        function showSlide(index) {
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === currentIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
            });

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === currentIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        }

        function nextSlide() {
            showSlide(currentIndex + 1);
        }

        function previousSlide() {
            showSlide(currentIndex - 1);
        }

        function startAutoplay() {
            if (reduceMotion || autoplayId !== null) {
                return;
            }

            autoplayId = window.setInterval(nextSlide, 6500);
        }

        function stopAutoplay() {
            if (autoplayId === null) {
                return;
            }

            window.clearInterval(autoplayId);
            autoplayId = null;
        }

        function restartAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        previousButton?.addEventListener('click', () => {
            previousSlide();
            restartAutoplay();
        });

        nextButton?.addEventListener('click', () => {
            nextSlide();
            restartAutoplay();
        });

        gallery.addEventListener('mouseenter', stopAutoplay);
        gallery.addEventListener('mouseleave', startAutoplay);
        gallery.addEventListener('focusin', stopAutoplay);
        gallery.addEventListener('focusout', startAutoplay);
        gallery.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                previousSlide();
                restartAutoplay();
            }

            if (event.key === 'ArrowRight') {
                nextSlide();
                restartAutoplay();
            }
        });

        showSlide(currentIndex);
        startAutoplay();
    }

    function setupReveal() {
        const items = [...document.querySelectorAll('.reveal-on-scroll')];

        if (items.length === 0) {
            return;
        }

        document.querySelectorAll('section').forEach((section) => {
            [...section.querySelectorAll('.reveal-on-scroll')].forEach((item, index) => {
                item.style.setProperty('--reveal-delay', `${Math.min(index * 90, 360)}ms`);
            });
        });

        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.12,
        });

        items.forEach((item) => observer.observe(item));
    }

    function setupParallaxImages() {
        const images = [...document.querySelectorAll('[data-parallax-image]')];

        if (reduceMotion || images.length === 0) {
            return;
        }

        let ticking = false;

        function update() {
            ticking = false;

            images.forEach((image) => {
                const rect = image.getBoundingClientRect();
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

                if (rect.bottom < 0 || rect.top > viewportHeight) {
                    return;
                }

                const progress = (rect.top - viewportHeight / 2) / viewportHeight;
                const offset = Math.max(-18, Math.min(18, progress * -28));
                image.style.transform = `scale(1.06) translateY(${offset}px)`;
            });
        }

        function requestUpdate() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        update();
    }

    function start() {
        setupGallery();
        setupReveal();
        setupParallaxImages();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
