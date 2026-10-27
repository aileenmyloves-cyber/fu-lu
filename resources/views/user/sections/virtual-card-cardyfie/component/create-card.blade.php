<div class="col-xl-12">
    <div class="row mb-30-none">
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __(@$page_title) }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <form class="card-form" action="{{ route('user.cardyfie.virtual.card.buy') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code class="d-block text-center">
                                            <span>{{ __("Exchange Rate") }} <span class="exchange-rate">--</span></span>
                                        </code>
                                    </div>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                     <label>{{ __("Card Holder's Name") }}<span>*</span></label>
                                    <input type="text" class="form--control" placeholder="{{ __("Enter Card Holder's Name") }}" name="name_on_card" value="{{ old('name_on_card',$card_customer->first_name.' '.$card_customer->last_name) }}" required>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <label>{{ __("Card Tier") }} <span class="text--base">*</span></label>
                                    <select class="form--control nice-select" name="card_tier" required>
                                        {{-- <option  disabled selected value="null" >{{ __('Choose One') }}</option> --}}
                                        <option value="universal">{{ __("Universal") }}</option>
                                        <option value="platinum">{{ __("Platinum") }}</option>
                                    </select>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <label>{{ __("Card Type") }} <span class="text--base">*</span></label>
                                    <select class="form--control nice-select" name="card_type" required>
                                        {{-- <option  disabled selected value="null" >{{ __('Choose One') }}</option> --}}
                                        <option value="visa">{{ __("Visa") }}</option>
                                        <option value="mastercard">{{ __("Mastercard") }}</option>
                                    </select>
                                </div>

                                <div class="col-xl-12 col-lg-12 form-group">
                                    <label>{{ __("Card Currency") }} <span class="text--base">*</span></label>
                                    <select class="form--control nice-select" name="currency" required>
                                        {{-- <option  disabled selected value="null" >{{ __('Choose One') }}</option> --}}
                                        @foreach ($supported_currency as $item)
                                            <option value="{{ $item->code }}"
                                                data-code="{{ $item->code }}"
                                                data-symbol="{{ $item->symbol }}"
                                                data-rate="{{ $item->rate }}"
                                                data-wallet="{{ $item->id }}"
                                                data-currency-id="{{ $item->id }}"
                                            data-name="{{ $item->country }}">{{ $item->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <label>{{ __("From Wallet") }} <span class="text--base">*</span></label>
                                    <select class="form--control select2-auto-tokenize" name="from_currency">
                                        @foreach ($from_wallets as $item)
                                        <option value="{{ $item->currency->code }}"
                                            data-code="{{ $item->currency->code }}"
                                            data-symbol="{{ $item->currency->symbol }}"
                                            data-rate="{{ $item->currency->rate }}"
                                            data-type="{{ $item->currency->type }}"
                                            data-balance="{{ $item->balance }}"
                                            data-wallet="{{ $item->id }}"
                                            data-currency-id="{{ $item->currency->id }}"
                                            data-name="{{ $item->currency->country }}">{{ucwords($item->currency->name) ."( ".get_amount($item->balance,$item->currency->code,get_wallet_precision())." )"}}</option>
                                        @endforeach
                                    </select>
                                    <div class="note-area mt-10">
                                        <code class="d-block">{{ __("Available Balance") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                    </div>
                                </div>

                                @if($basic_settings->user_pin_verification == true)
                                        <div class="col-xl-12 col-lg-12">
                                            <a href="javascript:void(0)" class="btn--base w-100 btn-loading buyBtn" data-bs-toggle="modal" data-bs-target="#checkPin">{{ __("buy Card") }} <i class="las la-plus-circle ms-1"></i></a>
                                        </div>
                                     </div>
                                    @include('user.components.modal.pin-check')
                                @else
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100 btn-loading buyBtn">{{ __("buy Card") }} <i class="las la-plus-circle ms-1"></i></button>
                                    </div>
                                </div>
                                @endif

                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{__("Preview")}}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Card Type") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fw-bold p-card-type">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Charge") }}</span> <span class="text--base card_tier_preview"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fees">--</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("Total Payable")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="last payable-total text-warning">--</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@push('script')
    @if(isset($card_customer))
        @if(isset($card_customer->status) && $card_customer->status ==  global_const()::CARD_CUSTOMER_APPROVED_STATUS)
            <script>
                var defualCurrency = "{{ get_default_currency_code() }}";
                var defualCurrencyRate = "{{ get_default_currency_rate() }}";
                $(document).ready(function(){
                    getExchangeRate();
                    getFees();
                    getPreview();
                });
                $("input[name=card_amount]").keyup(function(){
                    getFees();
                    getPreview();
                });

                $("select[name=currency]").change(function(){
                    getExchangeRate();
                    getFees();
                    getPreview();
                });
                $("select[name=from_currency]").change(function(){
                    getExchangeRate();
                    getFees();
                    getPreview();
                });

                $("select[name=card_tier]").change(function(){
                    getExchangeRate();
                    getFees();
                    getPreview();
                });
                $("select[name=card_type]").change(function(){
                    getPreview();
                });

                function acceptVar() {
                    var defualCurrency          = defualCurrency;
                    var defualCurrencyRate      = defualCurrencyRate;

                    var cCurrencySelected       = $("select[name=currency] :selected");
                    var currencyCode            = $("select[name=currency] :selected").val();
                    var currencyRate            = $("select[name=currency] :selected").data('rate');

                    var fCurrencySelected       = $("select[name=from_currency] :selected");
                    var fCurrencyCode           = $("select[name=from_currency] :selected").val();
                    var fCurrencyRate           = $("select[name=from_currency] :selected").data('rate');
                    var senderCurrencyType      = $("select[name=from_currency] :selected").data('type');

                    var cardType                 = $("select[name=card_tier] :selected").val();
                    var cardProvider             = $("select[name=card_type] :selected").val();


                    var currencyMinAmount    = "{{getAmount($cardCharge->min_limit)}}";
                    var currencyMaxAmount    = "{{getAmount($cardCharge->max_limit)}}";
                    var currencyDailyLimit   = "{{getAmount($cardCharge->daily_limit)}}";
                    var currencyMonthlyLimit = "{{getAmount($cardCharge->monthly_limit)}}";

                    var universal_card_issues_fee = "{{getAmount($cardCharge->universal_card_issues_fee)}}";
                    var platinum_card_issues_fee = "{{getAmount($cardCharge->platinum_card_issues_fee)}}";

                    if(senderCurrencyType == "CRYPTO"){
                        var senderPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
                    }else{
                        var senderPrecison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
                    }

                    return {
                        defualCurrency:defualCurrency,
                        defualCurrencyRate:defualCurrencyRate,

                        cCurrencySelected:cCurrencySelected,
                        currencyCode:currencyCode,
                        currencyRate:currencyRate,

                        cardType:cardType,
                        cardProvider:cardProvider,

                        fCurrencySelected:fCurrencySelected,
                        fCurrencyCode:fCurrencyCode,
                        fCurrencyRate:fCurrencyRate,
                        sPrecison:senderPrecison,

                        currencyMinAmount:currencyMinAmount,
                        currencyMaxAmount:currencyMaxAmount,
                        currencyDailyLimit:currencyDailyLimit,
                        currencyMonthlyLimit:currencyMonthlyLimit,

                        universalCardIssuesFee:universal_card_issues_fee,
                        platinumCardIssuesFee:platinum_card_issues_fee,


                    };
                }
                function getExchangeRate(){
                    var card_currency = acceptVar().currencyCode;
                    var card_currency_rate = acceptVar().currencyRate;

                    var from_currency = acceptVar().fCurrencyCode;
                    var from_currency_rate = acceptVar().fCurrencyRate;

                    var rate =  parseFloat(from_currency_rate)/parseFloat(card_currency_rate);
                    $('.exchange-rate').html("1 " + card_currency + " = " + parseFloat(rate).toFixed(acceptVar().sPrecison) + " " + from_currency);

                    return rate;
                }


                function feesCalculation() {
                    var from_currency_rate = acceptVar().fCurrencyRate;
                    var exchange_rate = getExchangeRate();
                    var sender_amount = $("input[name=card_amount]").val();
                    if( acceptVar().cardType == 'universal'){
                        var fixed_charge = acceptVar().universalCardIssuesFee;
                    }else if(acceptVar().cardType == 'platinum'){
                        var fixed_charge = acceptVar().platinumCardIssuesFee;
                    }
                    var percent_charge = 0;

                    if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) ) {
                        // Process Calculation
                        var fixed_charge_calc = parseFloat(fixed_charge) * parseFloat(exchange_rate);
                        var percent_charge_calc = percent_charge;
                        var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
                        total_charge = parseFloat(total_charge).toFixed(acceptVar().sPrecison);
                        // return total_charge;
                        return {
                            total: parseFloat(total_charge).toFixed(acceptVar().sPrecison),
                            fixed: parseFloat(fixed_charge_calc).toFixed(acceptVar().sPrecison),
                            percent: parseFloat(percent_charge).toFixed(acceptVar().sPrecison),
                        };
                    } else {
                        // return "--";
                        return false;
                    }
                }
                function getFees() {
                    var from_currency = acceptVar().fCurrencyCode;
                    var charges = feesCalculation();
                    if (charges == false) {
                        return false;
                    }

                    $(".fees-show").html("{{ __('Fees') }}: " + parseFloat(charges.total).toFixed(acceptVar().sPrecison) + " " + from_currency );
                }
                function getPreview() {
                        var exchange_rate = getExchangeRate();
                        var card_currency = acceptVar().currencyCode;
                        var from_currency = acceptVar().fCurrencyCode;
                        // Fees
                        var charges = feesCalculation();

                        $('.fees').html( parseFloat(charges.total).toFixed(acceptVar().sPrecison) + " " + from_currency);
                        $('.p-card-type').text( acceptVar().cardProvider.replace(/\b\w/g, l => l.toUpperCase()));
                        $('.card_tier_preview').text(" (" + acceptVar().cardType.replace(/\b\w/g, l => l.toUpperCase()) + ")");
                        $('.payable-total').html(parseFloat(charges.total).toFixed(acceptVar().sPrecison) + " " + from_currency);
                }

        </script>
        @endif
    @endif
@endpush
