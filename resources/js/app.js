import './bootstrap';

import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Alpine = Alpine;
window.Swal = Swal;

L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

Alpine.data('addressForm', (config) => ({
    endpoint: config.endpoint,
    form: {
        province_code: String(config.form.province_code || ''),
        city_code: String(config.form.city_code || ''),
        district_code: String(config.form.district_code || ''),
        village_code: String(config.form.village_code || ''),
        province: config.form.province || '',
        city: config.form.city || '',
        district: config.form.district || '',
        village: config.form.village || '',
        postal_code: config.form.postal_code || '',
        latitude: String(config.form.latitude || '-6.178306'),
        longitude: String(config.form.longitude || '106.631889'),
    },
    options: { province: [], city: [], district: [], village: [] },
    query: {
        province: config.form.province || '',
        city: config.form.city || '',
        district: config.form.district || '',
        village: config.form.village || '',
    },
    open: null,
    loading: null,
    map: null,
    marker: null,
    locationError: '',

    async init() {
        await this.load('province');

        const sequence = [
            ['city', this.form.province_code],
            ['district', this.form.city_code],
            ['village', this.form.district_code],
        ];

        for (const [type, parent] of sequence) {
            if (parent) {
                await this.load(type, parent);
            }
        }
    },

    async load(type, parent = null) {
        this.loading = type;

        try {
            const url = new URL(this.endpoint, window.location.origin);
            if (parent) url.searchParams.set('parent', parent);
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Data wilayah gagal dimuat.');
            const payload = await response.json();
            this.options[type] = payload.data || [];
        } catch (error) {
            this.options[type] = [];
            this.locationError = error.message;
        } finally {
            this.loading = null;
        }
    },

    filtered(type) {
        const keyword = String(this.query[type] || '').toLocaleLowerCase('id-ID').trim();
        if (!keyword) return this.options[type];

        return this.options[type].filter((item) => item.name.toLocaleLowerCase('id-ID').includes(keyword));
    },

    async select(type, option) {
        this.form[`${type}_code`] = option.code;
        this.form[type] = option.name;
        this.query[type] = option.name;
        this.open = null;

        const descendants = {
            province: ['city', 'district', 'village'],
            city: ['district', 'village'],
            district: ['village'],
            village: [],
        };

        descendants[type].forEach((child) => {
            this.form[`${child}_code`] = '';
            this.form[child] = '';
            this.query[child] = '';
            this.options[child] = [];
        });

        if (type === 'province') await this.load('city', option.code);
        if (type === 'city') await this.load('district', option.code);
        if (type === 'district') await this.load('village', option.code);
        if (type === 'village' && option.postal_code) this.form.postal_code = option.postal_code;
    },

    clearIfChanged(type) {
        if (this.query[type] !== this.form[type]) {
            this.form[`${type}_code`] = '';
            this.form[type] = '';
        }
    },

    showMap() {
        this.$nextTick(() => {
            const lat = Number.parseFloat(this.form.latitude) || -6.178306;
            const lng = Number.parseFloat(this.form.longitude) || 106.631889;

            if (!this.map) {
                this.map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(this.map);
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', (event) => this.setCoordinates(event.target.getLatLng()));
                this.map.on('click', (event) => {
                    this.marker.setLatLng(event.latlng);
                    this.setCoordinates(event.latlng);
                });
            } else {
                this.map.setView([lat, lng], this.map.getZoom());
                this.marker.setLatLng([lat, lng]);
            }

            setTimeout(() => this.map.invalidateSize(), 100);
        });
    },

    setCoordinates(position) {
        this.form.latitude = Number(position.lat).toFixed(7);
        this.form.longitude = Number(position.lng).toFixed(7);
        this.locationError = '';
    },

    useDeviceLocation() {
        this.locationError = '';

        if (!navigator.geolocation) {
            this.locationError = 'Perangkat ini tidak mendukung deteksi lokasi.';
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            const point = { lat: position.coords.latitude, lng: position.coords.longitude };
            this.setCoordinates(point);
            this.showMap();
        }, () => {
            this.locationError = 'Izin lokasi ditolak. Anda tetap dapat memilih titik secara manual.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    },
}));

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

    document.querySelectorAll('[data-cart-count-number]').forEach((label) => {
        label.textContent = String(numericCount);
    });

    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: numericCount } }));
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
