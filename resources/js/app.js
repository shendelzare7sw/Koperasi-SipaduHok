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
