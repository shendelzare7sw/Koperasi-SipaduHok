@props(['action'])

@if(config('services.turnstile.site_key'))
    <div class="flex flex-col items-center">
        <div
            class="cf-turnstile max-w-full"
            data-sitekey="{{ config('services.turnstile.site_key') }}"
            data-action="{{ $action }}"
            data-theme="light"
            data-size="flexible"
        ></div>
        @error('cf-turnstile-response')
            <p class="mt-2 text-center text-sm font-bold text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif
