import './bootstrap';
import Alpine from 'alpinejs';
import { initLandingMotion } from './landing-motion';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

/**
 * Modern Custom Confirm Dialog using SweetAlert2
 */
window.confirmDialog = function ({
    title = 'Konfirmasi Tindakan',
    text = '',
    html = '',
    icon = 'warning',
    confirmText = 'Ya, Lanjutkan',
    cancelText = 'Batal',
    confirmType = 'primary', // 'primary' | 'danger' | 'success'
    isDanger = false,
    onConfirm = null,
}) {
    const isDestructive = isDanger || confirmType === 'danger';
    const resolvedConfirmText = confirmText || (isDestructive ? 'Ya, Hapus' : 'Ya, Lanjutkan');
    const resolvedIcon = isDestructive && icon === 'warning' ? 'warning' : icon;

    return Swal.fire({
        title,
        text: html ? undefined : text,
        html: html || undefined,
        icon: resolvedIcon,
        showCancelButton: true,
        confirmButtonText: resolvedConfirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-custom-popup',
            title: 'swal2-custom-title',
            htmlContainer: 'swal2-custom-html',
            confirmButton: isDestructive
                ? 'swal2-custom-btn-danger'
                : confirmType === 'success'
                  ? 'swal2-custom-btn-success'
                  : 'swal2-custom-btn-primary',
            cancelButton: 'swal2-custom-btn-cancel',
            actions: 'swal2-custom-actions',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
            return true;
        }
        return false;
    });
};

/**
 * Modern Toast Notifications
 */
window.showToast = function (message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
        customClass: {
            popup: `swal2-custom-toast swal2-toast-${type}`,
        },
    });

    return Toast.fire({
        icon: type,
        title: message,
    });
};

Alpine.data('assistanceItems', (initial) => ({
    items: initial.map((item) => ({ ...item, key: crypto.randomUUID() })),
    add() {
        if (this.items.length < 30)
            this.items.push({ key: crypto.randomUUID(), nama: '', kategori_bantuan_id: '', jumlah: 1, satuan: '' });
    },
    remove(index) {
        if (this.items.length <= 1) return;
        const itemName = this.items[index]?.nama?.trim();
        const label = itemName ? `barang "${itemName}"` : `Barang ${index + 1}`;
        window.confirmDialog({
            title: 'Hapus Barang Ini?',
            text: `Apakah Anda yakin ingin menghapus ${label} dari daftar bantuan?`,
            icon: 'warning',
            confirmText: 'Ya, Hapus',
            confirmType: 'danger',
            onConfirm: () => {
                this.items.splice(index, 1);
            },
        });
    },
}));
window.Alpine = Alpine;
Alpine.data('givingGuide', () => ({
    active: 'donasi',
    keys: ['donasi', 'bantuan'],
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
    initLandingMotion(landing, reducedMotion);
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

/**
 * Global Confirm Interceptors & Flash Toast Runner
 */
function initConfirmInterceptors() {
    // 1. Sanitize legacy inline onsubmit containing confirm(...)
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
        const onsubmitStr = form.getAttribute('onsubmit') || '';
        const match = onsubmitStr.match(/confirm\(['"](.*?)['"]\)/);
        const msg = match ? match[1] : 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        form.removeAttribute('onsubmit');
        form.dataset.confirm = msg;

        const isDelete =
            onsubmitStr.toLowerCase().includes('hapus') ||
            !!form.querySelector('input[name="_method"][value="DELETE"]');
        if (isDelete) {
            form.dataset.confirmType = 'danger';
            form.dataset.confirmTitle = form.dataset.confirmTitle || 'Konfirmasi Hapus Data';
            form.dataset.confirmBtn = form.dataset.confirmBtn || 'Ya, Hapus Data';
        }
    });

    // 2. Sanitize legacy inline onclick containing confirm(...)
    document.querySelectorAll('button[onclick*="confirm("]').forEach((btn) => {
        const onclickStr = btn.getAttribute('onclick') || '';
        const match = onclickStr.match(/confirm\(['"](.*?)['"]\)/);
        const msg = match ? match[1] : 'Apakah Anda yakin?';
        btn.removeAttribute('onclick');
        btn.dataset.confirm = msg;
    });

    // 3. Delegate form submit with data-confirm or DELETE method
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        // Skip if already confirmed and programmatically submitted
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        const hasConfirm = form.dataset.confirm !== undefined;
        const isDeleteMethod = !!form.querySelector('input[name="_method"][value="DELETE"]');

        if (!hasConfirm && !isDeleteMethod) {
            return;
        }

        e.preventDefault();

        const message =
            form.dataset.confirm ||
            (isDeleteMethod
                ? 'Data ini akan dihapus secara permanen dari sistem.'
                : 'Apakah Anda yakin ingin melanjutkan tindakan ini?');
        const title =
            form.dataset.confirmTitle || (isDeleteMethod ? 'Konfirmasi Hapus Data' : 'Konfirmasi Tindakan');
        const confirmBtn = form.dataset.confirmBtn || (isDeleteMethod ? 'Ya, Hapus Data' : 'Ya, Lanjutkan');
        const confirmType = form.dataset.confirmType || (isDeleteMethod ? 'danger' : 'primary');
        const cancelBtn = form.dataset.confirmCancel || 'Batal';

        window.confirmDialog({
            title,
            text: message,
            confirmText: confirmBtn,
            cancelText: cancelBtn,
            confirmType,
            onConfirm: () => {
                form.dataset.confirmed = 'true';
                const submitBtn =
                    form.querySelector('button[type="submit"]') ||
                    form.querySelector('button:not([type="button"])');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
                form.submit();
            },
        });
    });

    // 4. Delegate button click with data-confirm
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-confirm]');
        if (!target) return;
        if (target.tagName.toLowerCase() === 'form') return; // Handled by submit

        const parentForm = target.closest('form');
        if (parentForm && (target.type === 'submit' || target.tagName.toLowerCase() === 'button')) {
            if (!parentForm.dataset.confirm) {
                parentForm.dataset.confirm = target.dataset.confirm;
                if (target.dataset.confirmTitle) parentForm.dataset.confirmTitle = target.dataset.confirmTitle;
                if (target.dataset.confirmBtn) parentForm.dataset.confirmBtn = target.dataset.confirmBtn;
                if (target.dataset.confirmType) parentForm.dataset.confirmType = target.dataset.confirmType;
            }
            return;
        }

        if (target.dataset.confirmed === 'true') {
            delete target.dataset.confirmed;
            return;
        }

        e.preventDefault();
        const message = target.dataset.confirm;
        const title = target.dataset.confirmTitle || 'Konfirmasi';
        const confirmBtn = target.dataset.confirmBtn || 'Ya, Lanjutkan';
        const confirmType = target.dataset.confirmType || 'primary';

        window.confirmDialog({
            title,
            text: message,
            confirmText: confirmBtn,
            confirmType,
            onConfirm: () => {
                target.dataset.confirmed = 'true';
                if (target.href) {
                    window.location.href = target.href;
                } else {
                    target.click();
                }
            },
        });
    });

    // 5. Auto toast from flash data
    const flashEl = document.getElementById('flash-data');
    if (flashEl) {
        const success = flashEl.dataset.success;
        const error = flashEl.dataset.error;
        const warning = flashEl.dataset.warning;
        const info = flashEl.dataset.info;

        if (success) {
            window.showToast(success, 'success');
        } else if (error) {
            window.showToast(error, 'error');
        } else if (warning) {
            window.showToast(warning, 'warning');
        } else if (info) {
            window.showToast(info, 'info');
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConfirmInterceptors);
} else {
    initConfirmInterceptors();
}
