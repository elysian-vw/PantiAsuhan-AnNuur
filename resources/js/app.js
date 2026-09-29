import './bootstrap';
import Alpine from 'alpinejs';

Alpine.data('assistanceItems', (initial) => ({
    items: initial.map((item) => ({ ...item, key: crypto.randomUUID() })),
    add() {
        if (this.items.length < 30)
            this.items.push({ key: crypto.randomUUID(), nama: '', kategori_bantuan_id: '', jumlah: 1, satuan: '' });
    },
}));
window.Alpine = Alpine;
Alpine.data('givingGuide', () => ({
    active: 'donasi',
    keys: ['donasi', 'bantuan', 'kunjungan'],
    select(key) {
        this.active = key;
        this.$nextTick(() => document.getElementById('tab-' + key)?.focus());
    },
    move(direction) {
        this.select(this.keys[(this.keys.indexOf(this.active) + direction + this.keys.length) % this.keys.length]);
    },
}));
Alpine.start();

const landing = document.querySelector('.landing-page');
if (landing) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const backToTop = landing.querySelector('.back-to-top');
    const progress = document.querySelector('.landing-progress span');
    let ticking = false;
    const updateScroll = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.transform = `scaleX(${max > 0 ? Math.min(1, window.scrollY / max) : 0})`;
        backToTop.hidden = window.scrollY < 600;
        ticking = false;
    };
    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(updateScroll);
            }
        },
        { passive: true },
    );
    window.addEventListener('resize', updateScroll);
    updateScroll();
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
        document.querySelector('.site-header .brand')?.focus({ preventScroll: true });
    });
    if ('IntersectionObserver' in window && !reducedMotion.matches) {
        const reveal = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.animate(
                            [
                                { opacity: 0.25, transform: 'translateY(18px)' },
                                { opacity: 1, transform: 'translateY(0)' },
                            ],
                            { duration: 500, easing: 'ease-out' },
                        );
                        reveal.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.12 },
        );
        landing
            .querySelectorAll('.section-heading, .guide-grid, .home-faq, .visit-banner, .about-grid')
            .forEach((el) => reveal.observe(el));
        const stopMotion = () => {
            if (reducedMotion.matches) {
                reveal.disconnect();
                landing.getAnimations({ subtree: true }).forEach((animation) => animation.cancel());
            }
        };
        reducedMotion.addEventListener('change', stopMotion);
    }
}

document.querySelectorAll('.sidebar nav a').forEach((link) => {
    if (link.href === window.location.href) link.setAttribute('aria-current', 'page');
});

const chartElement = document.getElementById('donation-chart');
if (chartElement) {
    import('chart.js/auto').then(({ default: Chart }) => {
        const points = JSON.parse(chartElement.dataset.chart);
        new Chart(chartElement, {
            type: 'bar',
            data: {
                labels: points.map((p) => p.label),
                datasets: [
                    {
                        label: 'Donasi bersih (Rp)',
                        data: points.map((p) => p.value),
                        backgroundColor: '#6f8c56',
                        borderRadius: 5,
                        maxBarThickness: 38,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value),
                        },
                    },
                },
            },
        });
    });
}
const calendarElement = document.getElementById('visit-calendar');
if (calendarElement) {
    Promise.all([
        import('@fullcalendar/core'),
        import('@fullcalendar/daygrid'),
        import('@fullcalendar/list'),
        import('@fullcalendar/core/locales/id'),
    ]).then(([{ Calendar }, dayGrid, list, locale]) => {
        const calendar = new Calendar(calendarElement, {
            plugins: [dayGrid.default, list.default],
            locale: locale.default,
            initialView: window.innerWidth < 700 ? 'listMonth' : 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
            events: JSON.parse(calendarElement.dataset.events),
            displayEventEnd: true,
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            windowResize: () => calendar.changeView(window.innerWidth < 700 ? 'listMonth' : 'dayGridMonth'),
        });
        calendar.render();
    });
}
