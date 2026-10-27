@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@section('content')

<div class="body-wrapper">
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __(@$page_title) }}</h3>
        </div>
    </div>
    <div class="row justify-content-center mb-30-none">
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __('Transaction Preview') }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-funnel-dollar"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __('Subtotal') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ get_amount($trade->rate, $trade->rateCurrency->code, get_wallet_precision($trade->rateCurrency)) }}</span>

                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-quarter"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __('fees And Charges') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ get_amount($total_charge, $trade->rateCurrency->code, get_wallet_precision($trade->rateCurrency)) }}</span>

                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-quarter"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __('You Will Pay') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ get_amount($total_charge + $trade->rate, $trade->rateCurrency->code, get_wallet_precision($trade->rateCurrency)) }}</span>

                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="last">{{ __('Seller will Pay') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning last">{{ get_amount($trade->amount, $trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)) }}</span>

                                </div>
                            </div>
                        </div>
                    </div>
                    <form class="card-form mt-20" action="{{ setRoute('user.marketplace.buy') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="note-area mb-3">
                                    <code class="d-block text--warning">{{ __('Available Balance') }}: {{ get_amount($userWallet->balance,$userWallet->currency->code, get_wallet_precision($userWallet->currency)) }}</code>

                                </div>
                            </div>

                            <input type="hidden" name="trade_id" value="{{ $trade->id }}">
                            <div class="col-xl-12">
                                <label>{{ __('Receiving Gateway') }}</label>
                                <select class="form--control nice-select" name="payment_gateway">
                                    <option value="">{{ __('Select Gateway') }}</option>
                                    @foreach ($payment_gatewaies as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($basic_settings->user_pin_verification == true)
                                    <div class="col-xl-12 col-lg-12 mt-3">
                                        <a href="javascript:void(0)" class="btn--base w-100 btn-loading" data-bs-toggle="modal" data-bs-target="#checkPin">{{ __("Confirm") }} <i class="fas fa-angle-right ms-1"></i></a>
                                    </div>
                                </div>
                                @include('user.components.modal.pin-check')
                            @else
                                <div class="col-xl-12 col-lg-12 mt-3">
                                    <button type="submit" class="btn--base w-100  btn-loading">{{ __('Confirm') }} <i class="fas fa-angle-right ms-1"></i></button>
                                </div>
                            </div>
                            @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

@endpush

