@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Transactions")])
@endsection

@section('content')
    <div class="body-wrapper">
        <div class="table-content">
            <div class="row">
                <div class="header-title">
                    <!-- table -->
                    <div class="table-area pt-20 pb-30">
                        <div class="d-flex justify-content-between">
                            <div class="dash-section-title">
                                <h4>{{ __('Profit Investment') }}</h4>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-xl-12">
                                <div class="table-area">
                                    <table class="custom-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __("Plan") }}</th>
                                                <th>{{ __("Duration") }}</th>
                                                <th>{{ __("Investment") }}</th>
                                                <th>{{ __("Profit") }}</th>
                                                <th>{{ __("Date") }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($profit_logs as $item)
                                                <tr>
                                                    <td data-label="{{ __('Plan') }}">{{ $item->invest->investPlan->name }}</td>
                                                    <td data-label="{{ __('Duration') }}">{{ $item->invest->investPlan->duration }} {{ __("Days") }}</td>
                                                    <td data-label="{{ __('Investment') }}">{{ get_amount($item->invest->invest_amount,$item->userWallet->currency->code,get_wallet_precision($item->userWallet->currency)) }}</td>
                                                    <td data-label="{{ __('Profit') }}">{{ get_amount($item->profit_amount,$item->userWallet->currency->code,get_wallet_precision($item->userWallet->currency))  }}</td>
                                                    <td data-label="{{ __('Date') }}">{{ $item->created_at->format("d-m-Y H:i") }}</td>
                                                </tr>
                                            @empty
                                                @include('admin.components.alerts.empty',['colspan' => 10 , 'class' => "alert-warning"])
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{ get_paginate($profit_logs) }}

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
