@php
    // Only ask when there's a consent-requiring tag to gate. Cloudflare
    // Analytics is cookieless and needs no permission.
    $needsConsent = (bool) config('analytics.ga4_id');
    $consentKey = config('analytics.consent_cookie', 'gymdog_consent');
@endphp

@if ($needsConsent)
    <div x-data="{
            show: false,
            init() {
                try { this.show = localStorage.getItem(@json($consentKey)) === null; }
                catch (e) { this.show = false; }
            },
            choose(decision) {
                try { localStorage.setItem(@json($consentKey), decision); } catch (e) {}
                if (window.gtag) {
                    const granted = decision === 'granted';
                    gtag('consent', 'update', {
                        ad_storage: granted ? 'granted' : 'denied',
                        ad_user_data: granted ? 'granted' : 'denied',
                        ad_personalization: granted ? 'granted' : 'denied',
                        analytics_storage: granted ? 'granted' : 'denied'
                    });
                }
                this.show = false;
            }
         }"
         x-show="show"
         x-cloak
         x-transition.opacity
         class="fixed inset-x-0 bottom-0 z-50 p-4"
         role="dialog"
         aria-live="polite"
         aria-label="Cookie consent">
        <div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white/95 backdrop-blur shadow-lg p-5
                    flex flex-col sm:flex-row sm:items-center gap-4">
            <p class="text-sm text-gray-600 flex-1">
                We use cookies to understand how the site is used, so we can make it better.
                You can decline and everything will still work.
            </p>
            <div class="flex gap-2 shrink-0">
                <button type="button" @click="choose('denied')"
                        class="rounded-full px-4 py-2 text-sm border border-gray-200 hover:bg-gray-50">
                    Decline
                </button>
                <button type="button" @click="choose('granted')"
                        class="rounded-full px-4 py-2 text-sm font-semibold text-white"
                        style="background: var(--brand-primary)">
                    Accept
                </button>
            </div>
        </div>
    </div>
@endif
