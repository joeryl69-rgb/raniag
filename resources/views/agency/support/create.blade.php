<x-app-layout>
    <x-slot name="header">{{ __('Support Center') }}</x-slot>

    <div class="rg-support-hero">
        <span class="rg-support-hero__badge"><i class="bi bi-headset me-1"></i>SUPPORT CENTER</span>
        <h1 class="rg-support-hero__title">Contact Support</h1>
        <p class="rg-support-hero__sub mb-0">Report an issue or send feedback to the RANIAG / {{ config('raniag.organization') }} team. Your message is linked to your staff account.</p>
    </div>

    <x-support-form
        :action="route('agency.support.store')"
        :categories="$categories"
        :name-value="$user->name"
        :email-value="$user->email"
        :lock-identity="true"
        :back-url="route('dashboard')"
        org-label="RANIAG Support"
    />
</x-app-layout>
