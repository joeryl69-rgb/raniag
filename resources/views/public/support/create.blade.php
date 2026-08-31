@extends('layouts.public')
@section('title', 'Support Center')
@section('content')
<div class="container">
    <div class="rg-support-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <span class="rg-support-hero__badge"><i class="bi bi-headset me-1"></i>SUPPORT CENTER</span>
                <h1 class="rg-support-hero__title">Contact Support</h1>
                <p class="rg-support-hero__sub mb-0">Share your concerns and we'll help resolve them as quickly as possible.</p>
            </div>
            <a href="{{ route('public.home') }}" class="btn btn-outline-light rg-support-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <x-support-form :action="route('public.feedback.store')" :categories="$categories" :lock-identity="false" :back-url="route('public.home')" />
</div>
@endsection
