<x-app-layout>
    <x-slot name="header">{{ __('Support Center') }}</x-slot>

    <div class="rg-support-page">
        <div class="rg-support-form-intro">
            <span><i class="bi bi-chat-square-text" aria-hidden="true"></i> MESSAGE THE TEAM</span>
            <p>Choose a topic and give us enough detail to route your message.</p>
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
    </div>
</x-app-layout>
