// Kydesk — client JS
(function () {
    'use strict';

    // Flash toast autohide
    document.querySelectorAll('.fixed.top-4.right-4').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .35s, transform .35s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-12px)';
            setTimeout(() => el.remove(), 400);
        }, 4200);
    });

    // Global shortcuts
    document.addEventListener('keydown', (e) => {
        // ⌘K / Ctrl+K abre palette si existe (gestionado por Alpine)
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            const body = document.body;
            if (body.__x && body.__x.$data) {
                e.preventDefault();
                body.__x.$data.cmd = !body.__x.$data.cmd;
            }
        }
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        // N = nuevo ticket
        if ((e.key === 'n' || e.key === 'N') && !e.metaKey && !e.ctrlKey) {
            const link = document.querySelector('a[href*="/tickets/create"]');
            if (link) { e.preventDefault(); location.href = link.href; }
        }
    });

    // Prevent double submit
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], button:not([type])');
            if (btn && !btn.disabled) {
                setTimeout(() => { btn.disabled = true; btn.style.opacity = '.65'; btn.style.cursor = 'wait'; }, 10);
            }
        });
    });

    // Reveal fallback cuando no hay GSAP
    if (!window.gsap) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    en.target.style.transition = 'opacity .7s ease, transform .7s cubic-bezier(.16,1,.3,1)';
                    en.target.style.opacity = '1';
                    en.target.style.transform = 'translateY(0)';
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px' });
        document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
    }

    // GSAP hook
    if (window.gsap) {
        try { gsap.registerPlugin(window.ScrollTrigger); } catch (e) {}
    }
})();
