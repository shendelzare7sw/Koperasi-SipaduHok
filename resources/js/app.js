import './bootstrap';

import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

const swalTheme = {
    confirmButtonColor: '#165fac',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    customClass: {
        popup: 'sipaduhok-dialog',
        confirmButton: 'sipaduhok-dialog-button',
        cancelButton: 'sipaduhok-dialog-button',
    },
};

const rupiahDigits = (value) => String(value || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

const formatRupiah = (value) => {
    const digits = rupiahDigits(value);

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const normaliseRupiahInputs = (form) => {
    form.querySelectorAll('[data-rupiah-input]').forEach((input) => {
        input.value = rupiahDigits(input.value);
    });
};

document.addEventListener('input', (event) => {
    if (event.target.matches('[data-rupiah-input]')) {
        event.target.value = formatRupiah(event.target.value);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form');

    if (! form) {
        return;
    }

    if (! form.matches('[data-confirm]') || form.dataset.confirmed === 'true') {
        normaliseRupiahInputs(form);

        return;
    }

    event.preventDefault();
    const submitter = event.submitter;

    const result = await Swal.fire({
        ...swalTheme,
        title: form.dataset.confirmTitle || 'Konfirmasi tindakan',
        text: form.dataset.confirm || 'Pastikan data yang Anda masukkan sudah benar.',
        icon: form.dataset.confirmIcon || 'question',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Ya, lanjutkan',
        cancelButtonText: form.dataset.cancelButton || 'Batal',
    });

    if (! result.isConfirmed) {
        return;
    }

    form.dataset.confirmed = 'true';
    normaliseRupiahInputs(form);

    if (typeof form.requestSubmit === 'function') {
        submitter ? form.requestSubmit(submitter) : form.requestSubmit();
    } else {
        form.submit();
    }
});

const updateCartIndicators = (count) => {
    const numericCount = Number.parseInt(count, 10) || 0;
    const displayCount = numericCount > 99 ? '99+' : String(numericCount);

    document.querySelectorAll('[data-header-cart]').forEach((link) => {
        link.setAttribute('aria-label', `Buka keranjang, ${numericCount} produk`);
    });

    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
        badge.textContent = displayCount;
        badge.classList.toggle('hidden', numericCount < 1);
        badge.classList.toggle('grid', numericCount > 0);
    });

    document.querySelectorAll('[data-cart-count-text]').forEach((label) => {
        label.textContent = numericCount > 0 ? `(${numericCount})` : '';
    });
};

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-add-to-cart]');

    if (! form) {
        return;
    }

    event.preventDefault();
    const button = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
    button?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json();

        if (! response.ok) {
            const validationMessage = Object.values(payload.errors || {}).flat()[0];
            throw new Error(validationMessage || payload.message || 'Produk gagal ditambahkan ke keranjang.');
        }

        updateCartIndicators(payload.cart_count);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: payload.message,
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
        });
    } catch (error) {
        Swal.fire({
            ...swalTheme,
            icon: 'error',
            title: 'Produk belum ditambahkan',
            text: error.message,
            confirmButtonText: 'Mengerti',
        });
    } finally {
        button?.removeAttribute('disabled');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const toast = document.querySelector('[data-flash-success]');
    const error = document.querySelector('[data-flash-error]');

    document.querySelectorAll('[data-rupiah-input]').forEach((input) => {
        input.value = formatRupiah(input.value);
    });

    if (toast?.dataset.flashSuccess) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: toast.dataset.flashSuccess,
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
        });
    }

    if (error?.dataset.flashError) {
        Swal.fire({
            ...swalTheme,
            icon: 'error',
            title: 'Periksa kembali data Anda',
            text: error.dataset.flashError,
            confirmButtonText: 'Mengerti',
        });
    }
});
