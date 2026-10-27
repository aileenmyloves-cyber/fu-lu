@extends('user.layouts.master')

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

    </div>
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __(@$page_title) }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="exchange-area-wrapper text-center">
                            <div class="exchange-area mb-20">
                                <code class="d-block text-center"><span>{{ __("Current Balance") }}</span>
                                    {{ get_amount($myCard->amount,$myCard->currency,get_wallet_precision()) }}
                                </code>
                            </div>
                        </div>
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-credit-card"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Card Name") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning">{{ __((ucwords(@$myCard->card_name))) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-id-card-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("cardI d") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning">{{ __((ucwords(@$myCard->ulid))) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-crown"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Card Tier") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __(ucwords(@$myCard->card_tier)) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-list"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Card Type") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ ucwords(@$myCard->card_type) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-user-tag"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("customer Id") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{@$myCard->customer_ulid }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-sort-numeric-down"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("card Number") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    @php
                                        $card_pan =  $card_api['real_pan'] != null ? str_split(remove_special_char($card_api['real_pan']), 4) : str_split($card_api['masked_pan'], 4);
                                    @endphp
                                    @foreach($card_pan as $key => $value)
                                        <span>{{ @$value }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-ellipsis-h"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Cvv") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __($card_api['cvv'] ?? "***") }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-business-time"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("expiration")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{@$myCard->card_exp_time }}</span>
                                </div>
                            </div>


                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-stream"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Card Environment") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ $myCard->env }}</span>
                                </div>
                            </div>
                            @if($myCard->status == global_const()::CARD_CLOSED_STATUS)
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-file-archive"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Card Status") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="{{ $myCard->cardStatus->class }}">{{ $myCard->cardStatus->value }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($myCard->status == global_const()::CARD_ENABLED_STATUS || $myCard->status == global_const()::CARD_FREEZE_STATUS)
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-battery-half"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{__("Status") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <div class="toggle-container">
                                            @include('admin.components.form.switcher',[
                                                'name'          => 'status',
                                                'value'         => old('status',@$myCard->status ),
                                                'options'       => [__('UnFreeze') => global_const()::CARD_ENABLED_STATUS,__('Freeze') => global_const()::CARD_FREEZE_STATUS],
                                                'onload'        => true,
                                                'data_target'   => @$myCard->id,
                                            ])
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Billing Address") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <ul class="billing-list">
                            <li>
                                <h6>{{ __($myCard->address) }}</h6>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

<script>
    $(document).ready(function(){
        switcherAjax("{{ setRoute('user.cardyfie.virtual.card.change.status') }}");
    })

</script>

@endpush
