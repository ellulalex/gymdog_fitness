@php
    $ga4 = config('analytics.ga4_id');
    $cfToken = config('analytics.cloudflare_token');
    $consentKey = config('analytics.consent_cookie', 'gymdog_consent');
@endphp

{{-- Cloudflare Web Analytics: cookieless, so it runs without consent. --}}
@if ($cfToken)
    <script defer src="https://static.cloudflareinsights.com/beacon.min.js"
            data-cf-beacon='{"token": "{{ $cfToken }}"}'></script>
@endif

@if ($ga4)
    {{--
        GA4 via Consent Mode v2. The tag loads immediately but every storage
        type starts denied, so no cookies are set until the visitor accepts.
        Declining still yields cookieless modelled pings rather than nothing.
    --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        var stored = null;
        try { stored = localStorage.getItem(@json($consentKey)); } catch (e) {}

        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            wait_for_update: 500
        });

        if (stored === 'granted') {
            gtag('consent', 'update', {
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted',
                analytics_storage: 'granted'
            });
        }

        gtag('js', new Date());
        gtag('config', @json($ga4));
    </script>
@endif
