@php
    $default = get_default_language_code();
@endphp
@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __('Marketplace Transaction List') }}</h3>
        </div>
    </div>
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-list-wrapper">
            @forelse ($transactions as $item)
                @include('user.sections.marketplace.marketplace_transaction', compact('item'))
            @empty
            <div class="alert alert-primary text-center">
                {{ __("No Record Found!") }}
            </div>
            @endforelse
        </div>
    </div>
</div>
<nav>
    {{ $transactions->links() }}
</nav>
@endsection

@push('script')

@endpush
