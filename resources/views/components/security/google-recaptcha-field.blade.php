@props([
    'site_key',
    'extension',
    'position'  => 'start',
])

@php
    $position = match($position){
        'center'    => "justify-content-center",
        'start'     => "justify-content-start",
        'end'       => "justify-content-end",
    };
@endphp

@if ($extension->status ?? false)

    <div class="mb-4 d-flex {{ $position }}">
        <div class="g-recaptcha" data-sitekey="{{ $site_key }}" data-theme="light" data-callback="googleV2CaptchaCallback"></div>
    </div>

    @pushOnce('css')
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endPushOnce

    @push('script')
        <script>
            function googleV2CaptchaCallback(token){
                // handle token
            }
        </script>
    @endpush
@endif
