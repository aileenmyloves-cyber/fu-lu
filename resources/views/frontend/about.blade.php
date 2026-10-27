@extends('frontend.layouts.master')

@push("css")

@endpush

@section('content')

    @foreach ($page_section->sections ?? [] as $item)

        @if ( $item->section->key == 'banner-section')
            @include('frontend.partials.banner-section')
        @elseif($item->section->key == 'banner-floting')
            @include('frontend.partials.banner-floting')
        @elseif($item->section->key == 'work-section')
            @include('frontend.partials.how-work')
        @elseif($item->section->key == 'about-section')
            @include('frontend.partials.about')
        @elseif($item->section->key == 'security-section')
            @include('frontend.partials.security-section')
        @elseif($item->section->key == 'overview-section')
            @include('frontend.partials.map-section')
        @elseif($item->section->key == 'why-choose-us-section')
            @include('frontend.partials.choose-section')
        @elseif($item->section->key == 'testimonials-section')
            @include('frontend.partials.testimonials')
        @elseif($item->section->key == 'brand-section')
            @include('frontend.partials.brand-section')
        @elseif($item->section->key == 'faq-section')
            @include('frontend.partials.faq')
        @elseif($item->section->key == 'service-section')
            @include('frontend.partials.service')
        @elseif($item->section->key == 'contact-section')
            @include('frontend.partials.contact-section')
        @endif

    @endforeach


@endsection


@push("script")

@endpush
