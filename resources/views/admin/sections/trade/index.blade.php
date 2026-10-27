@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Trade Logs'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                @if(count($transactions) > 0)
                    <div class="table-btn-area">
                        <a href="{{ setRoute('admin.trade.export.data') }}" class="btn--base"><i class="fas fa-download me-1"></i>{{ __("Export Data") }}</a>
                    </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __("TRX") }}</th>
                            <th>{{ __("Email") }}</th>
                            <th>{{ __("Selling Amount") }}</th>
                            <th>{{ __("Asking Rate") }}</th>
                            <th>{{ __("Status") }}</th>
                            <th>{{ __("Time") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions  as $key => $item)
                            <tr>
                                <td>{{ $item->trx_id }}</td>
                                <td>{{ $item->user->email ?? 'N/A' }}</td>
                                <td>{{ get_amount($item->request_amount, $item->trade->saleCurrency->code, get_wallet_precision($item->trade->saleCurrency)) }}</td>
                                <td>{{ get_amount($item->trade->rate, $item->trade->rateCurrency->code, get_wallet_precision($item->trade->rateCurrency)) }}</td>
                                <td>
                                    <span class="{{ $item->tradeStringStatus->class }}">{{ $item->tradeStringStatus->value }}</span>
                                </td>
                                <td>{{ dateFormat('d M y h:i:s A', $item->created_at) }}</td>
                                <td>
                                    @if ($item->status == 0)
                                        <button type="button" class="btn btn--base bg--success"><i
                                                class="las la-check-circle"></i></button>
                                        <button type="button" class="btn btn--base bg--danger"><i
                                                class="las la-times-circle"></i></button>
                                        <a href="add-logs-edit.html" class="btn btn--base"><i class="las la-expand"></i></a>
                                    @endif
                                </td>
                                <td>
                                    @include('admin.components.link.info-default',[
                                        'href'          => setRoute('admin.trade.details', $item->id),
                                        'permission'    => "admin.trade.details",
                                    ])
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 8])
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ get_paginate($transactions) }}
        </div>
    </div>
@endsection

@push('script')
@endpush
