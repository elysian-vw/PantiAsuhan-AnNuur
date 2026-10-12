// Keep content visible by default: motion enhances the page without gating access.
export function initLandingMotion(landing, reducedMotion) {
    if (!('IntersectionObserver' in window) || typeof landing.animate !== 'function') return;

    const running = new Map();
    const revealed = new WeakSet();
    const heroItems = [...landing.querySelectorAll('.landing-hero-copy > *, .landing-hero-visual')];
    const targets = landing.querySelectorAll(
        '.landing-way, .section-heading, .card-grid > *, .guide-intro, .guide-panels, .home-faq > *, .landing-invitation, .landing-title',
    );
    const play = (element, delay = 0) => {
        if (reducedMotion.matches || revealed.has(element)) return;
        revealed.add(element);
        const animation = element.animate(
            [
                { opacity: 0, transform: 'translateY(22px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ],
            { duration: 650, delay, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'backwards' },
        );
        running.set(element, animation);
        animation.finished.catch(() => {}).finally(() => running.delete(element));
    };
    const observer = new IntersectionObserver(
        (entries) => {
            let index = 0;
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                play(entry.target, Math.min(index++ * 85, 255));
                observer.unobserve(entry.target);
            }
        },
        { threshold: 0.08 },
    );
    const syncPreference = () => {
        observer.disconnect();
        if (reducedMotion.matches) {
            running.forEach((animation) => animation.cancel());
            running.clear();
            return;
        }
        heroItems.forEach((element, index) => {
            // Don't replay the hero when restoring a page at a lower scroll position.
            if (element.getBoundingClientRect().bottom > 0) play(element, Math.min(index * 90, 360));
        });
        targets.forEach((element) => {
            if (!revealed.has(element)) observer.observe(element);
        });
    };
    // Keyboard users never have to wait for a focused link or control to appear.
    landing.addEventListener('focusin', (event) => {
        running.forEach((animation, element) => {
            if (element.contains(event.target)) animation.cancel();
        });
    });
    reducedMotion.addEventListener('change', syncPreference);
    syncPreference();
}
