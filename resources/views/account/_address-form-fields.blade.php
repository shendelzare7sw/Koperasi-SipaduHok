@php
    $address ??= null;
    $useOld ??= false;
    $fieldValue = fn (string $field, mixed $fallback = '') => $useOld
        ? old($field, data_get($address, $field, $fallback))
        : data_get($address, $field, $fallback);
    $formConfig = [
        'province_code' => $fieldValue('province_code'), 'city_code' => $fieldValue('city_code'),
        'district_code' => $fieldValue('district_code'), 'village_code' => $fieldValue('village_code'),
        'province' => $fieldValue('province'), 'city' => $fieldValue('city'),
        'district' => $fieldValue('district'), 'village' => $fieldValue('village'),
        'postal_code' => $fieldValue('postal_code'),
        'latitude' => $fieldValue('latitude', '-6.1783060'),
        'longitude' => $fieldValue('longitude', '106.6318890'),
    ];
@endphp

<div x-data="addressForm({ endpoint: {{ Illuminate\Support\Js::from(route('account.regions.index')) }}, form: {{ Illuminate\Support\Js::from($formConfig) }} })" x-init="init(); @if($useOld) $nextTick(() => showMap()) @endif" @address-form-visible.window="if ($event.detail === '{{ $formKey }}') showMap()" class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm font-semibold">Label alamat<input name="label" value="{{ $fieldValue('label') }}" placeholder="Rumah, Kantor" required maxlength="50" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    <label class="text-sm font-semibold">Nama penerima<input name="recipient_name" value="{{ $fieldValue('recipient_name', auth()->user()->name) }}" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    <label class="text-sm font-semibold sm:col-span-2">Nomor HP<input name="phone" type="tel" inputmode="tel" value="{{ $fieldValue('phone', auth()->user()->phone) }}" required maxlength="20" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>

    @foreach([['province', 'Provinsi', null], ['city', 'Kabupaten/Kota', 'province_code'], ['district', 'Kecamatan', 'city_code'], ['village', 'Kelurahan/Desa', 'district_code']] as [$type, $label, $parentField])
        <div class="relative text-sm font-semibold" @click.outside="if (open === '{{ $type }}') open = null">
            <label for="{{ $formKey }}-{{ $type }}">{{ $label }}</label>
            <input type="hidden" name="{{ $type }}_code" x-model="form.{{ $type }}_code">
            <div class="relative mt-1">
                <input id="{{ $formKey }}-{{ $type }}" type="search" x-model="query.{{ $type }}" @focus="open = '{{ $type }}'" @input="open = '{{ $type }}'; clearIfChanged('{{ $type }}')" @keydown.escape="open = null" :disabled="{{ $parentField ? '! form.'.$parentField : 'false' }}" autocomplete="off" placeholder="Cari {{ strtolower($label) }}..." required class="w-full rounded-xl border border-slate-300 py-3 pl-4 pr-11 font-normal focus:border-primary focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-slate-100">
                <button type="button" @click="open = open === '{{ $type }}' ? null : '{{ $type }}'" :disabled="{{ $parentField ? '! form.'.$parentField : 'false' }}" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400" aria-label="Buka pilihan {{ strtolower($label) }}"><i class="fas fa-chevron-down text-xs"></i></button>
            </div>
            <div x-cloak x-show="open === '{{ $type }}'" x-transition.origin.top class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                <p x-show="loading === '{{ $type }}'" class="px-3 py-3 font-normal text-slate-500"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat wilayah...</p>
                <template x-for="option in filtered('{{ $type }}')" :key="option.code"><button type="button" @click="select('{{ $type }}', option)" class="block w-full rounded-lg px-3 py-2.5 text-left font-normal text-slate-700 hover:bg-blue-50 hover:text-primary focus:bg-blue-50 focus:outline-none" x-text="option.name"></button></template>
                <p x-show="loading !== '{{ $type }}' && filtered('{{ $type }}').length === 0" class="px-3 py-3 font-normal text-slate-500">Wilayah tidak ditemukan.</p>
            </div>
        </div>
    @endforeach

    <label class="text-sm font-semibold sm:col-span-2">Nama jalan<input name="street" value="{{ $fieldValue('street') }}" placeholder="Contoh: Jl. Pendidikan" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    <label class="text-sm font-semibold">Nomor rumah / blok<input name="house_number" value="{{ $fieldValue('house_number') }}" placeholder="10 atau Blok C2" maxlength="50" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    <div class="grid grid-cols-2 gap-3">
        <label class="text-sm font-semibold">RT<input name="rt" value="{{ $fieldValue('rt') }}" inputmode="numeric" pattern="[0-9]{1,3}" placeholder="002" maxlength="3" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
        <label class="text-sm font-semibold">RW<input name="rw" value="{{ $fieldValue('rw') }}" inputmode="numeric" pattern="[0-9]{1,3}" placeholder="003" maxlength="3" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    </div>
    <label class="text-sm font-semibold">Kode pos <span class="font-normal text-slate-400">(bisa diubah)</span><input name="postal_code" x-model="form.postal_code" inputmode="numeric" pattern="[0-9]{5}" required maxlength="5" placeholder="15111" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20"></label>
    <label class="text-sm font-semibold sm:col-span-2">Patokan / detail tambahan<textarea name="landmark" rows="2" maxlength="500" placeholder="Rumah pagar hijau, sebelah minimarket" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary/20">{{ $fieldValue('landmark') }}</textarea></label>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 sm:col-span-2">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-extrabold text-slate-900"><i class="fas fa-map-location-dot mr-2 text-primary"></i>Titik lokasi pengantaran</h3><p class="mt-1 text-xs leading-5 text-slate-500">Klik peta atau geser pin. Titik ini menjadi tautan navigasi untuk kurir.</p></div><button type="button" @click="useDeviceLocation()" class="shrink-0 rounded-xl border border-primary bg-white px-4 py-2.5 text-xs font-bold text-primary hover:bg-blue-50"><i class="fas fa-location-crosshairs mr-2"></i>Gunakan Lokasi Saya</button></div>
        <div x-ref="map" class="h-64 w-full border-y border-slate-200 bg-slate-200 sm:h-72"></div>
        <input type="hidden" name="latitude" x-model="form.latitude"><input type="hidden" name="longitude" x-model="form.longitude">
        <div class="p-4 text-xs"><p class="font-mono text-slate-600"><span x-text="form.latitude"></span>, <span x-text="form.longitude"></span></p><p x-cloak x-show="locationError" class="mt-2 font-semibold text-red-600" x-text="locationError"></p></div>
    </section>
    <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_primary" type="checkbox" value="1" @checked($useOld ? old('is_primary') : data_get($address, 'is_primary')) class="rounded border-slate-300 text-primary focus:ring-primary">Jadikan alamat utama</label>
</div>
