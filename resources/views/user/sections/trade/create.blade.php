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
    <div class="row mb-30-none">
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __('Start Transaction') }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="intervals" data-intervals="{{ $intervals }}"></div>
                        <form action="{{ setRoute('user.trade.submit') }}" method="post" class="card-form">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code
                                            class="d-block text-center"><span> {{ __('Selling Exchange Rate') }}</span>  <span class="exchange_rate">--</span></code>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __('Selling Amount') }} <span class="text--base">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form--control" placeholder="0.00" name="amount" maxlength="20" value="{{ old('amount') }}" required>
                                        <select class="form--control nice-select" style="display: none;" name="currency">
                                            @foreach ($currency as $item)
                                                <option value="{{ $item->id }}"
                                                    data-id="{{ $item->id }}"
                                                    data-type="{{ $item->type }}"
                                                    data-currency="{{ $item->code }}"
                                                    data-rate="{{ $item->rate }}"
                                                >{{ $item->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __('Asking Rate') }} <span class="text--base">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form--control rate_amount" placeholder="0.00" name="rate" required maxlength="20" value="{{ old('rate') }}">
                                        <select class="form--control nice-select" style="display: none;" name="rate_currency">
                                            @foreach ($user_wallets as $item)
                                            <option
                                                value="{{ $item->currency->id }}"
                                                data-id="{{ $item->currency->id }}"
                                                data-rate="{{ $item->currency->rate }}"
                                                data-code="{{ $item->currency->code }}"
                                                data-type="{{ $item->currency->type }}"
                                                {{ get_default_currency_code() == $item->currency->code ? "selected": "" }}
                                                    >{{ $item->currency->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <div class="note-area">
                                        <code class="d-block balance-show"></code>
                                        <code class="d-block fees-show">{{ __('charge') }}: 0.00 USD + 0.00% = 0.00 USD</code>
                                    </div>
                                </div>
                                @if($basic_settings->user_pin_verification == true)
                                        <div class="col-xl-12 col-lg-12">
                                            <a href="javascript:void(0)" class="btn--base w-100 btn-loading" data-bs-toggle="modal" data-bs-target="#checkPin">{{ __("Continue") }} <i class="fas fa-angle-right ms-1"></i></a>
                                        </div>
                                    </div>
                                    @include('user.components.modal.pin-check')
                                @else
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100 btn-loading">{{ __('Continue') }} <i class="fas fa-angle-right ms-1"></i></button>
                                    </div>
                                </div>
                                @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __('Preview') }}</h5>
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
                                            <span>{{ __('Enter Selling Amount') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="request_amount">--</span>
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
                                    <span class="total_fees">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __('Buyer Will Pay') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="buyer_will_pay">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="last">{{ __('You Will Pay') }}:</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning last will_pay">--</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            {{-- limit section  --}}
            <div class="dash-payment-item-wrapper limit">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{__("Limit Information")}}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-wallet"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Transaction Limit") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="limit-show">--</span>
                                </div>
                            </div>
                            @if (@$charges->daily_limit > 0)
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Daily Limit") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="limit-daily">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Remaining Daily Limit") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="daily-remaining">--</span>
                                    </div>
                                </div>
                            @endif
                            @if (@$charges->monthly_limit > 0)
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Monthly Limit") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="limit-monthly">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Remaining Monthly Limit") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="monthly-remaining">--</span>
                                    </div>
                                </div>
                            @endif

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


@push('script')
    <script>
        $(document).ready(function () {
            $("select").niceSelect(); // Ensure nice-select initializes
            getPreview();
            getFees();
            senderBalance();
            getDailyMonthlyLimit();
            get_remaining_limits();
        });

        $('select[name=rate_currency]').on('change',function(){
            var asking_rate = $("select[name=rate_currency] :selected").attr("data-rate");
            getPreview();
            getFees();
            getDailyMonthlyLimit();
            get_remaining_limits();
        });

        $('select[name=currency]').on('change',function(){
            getPreview();
            getFees();
            senderBalance();
            getDailyMonthlyLimit();
            get_remaining_limits();
        });

        $("input[name=amount]").keyup(function(){
            getPreview();
            getFees();
            get_remaining_limits();
        });

        $("input[name=rate]").keyup(function(){
            getPreview();
            getFees();
            get_remaining_limits();
        });

        // get sender wallet balance by currency
        function senderBalance() {
            var senderCurrencyId = $("select[name=currency] :selected").attr("data-id");

            $.ajax({
                type: 'GET',
                url: "{{ route('user.trade.available.balance.byCurrency') }}",
                data: { id: senderCurrencyId },
                success: function(data) {
                    $('.balance-show').html("{{ __('availabe Blance') }}: " + parseFloat(data).toFixed(acceptVar().currencyPrecison) + ' ' + $("select[name=currency] :selected").attr("data-currency"));
                },
                // error: function(xhr, status, error) {
                //     console.log('Error: ' + error);
                // }
            });
        }

        function acceptVar() {
            var selectedVal = $("select[name=currency] :selected");
            var currencyCode = $("select[name=currency] :selected").attr("data-currency");
            var currencyRate = $("select[name=currency] :selected").attr("data-rate");
            var currencyType = $("select[name=currency] :selected").attr("data-type");

            var asking_rate_value = $("select[name=rate_currency] :selected");
            var asking_rate_code = $("select[name=rate_currency] :selected").attr("data-code");
            var asking_rate = $("select[name=rate_currency] :selected").attr("data-rate");
            var asking_rate_type = $("select[name=rate_currency] :selected").attr("data-type");

            var currencyDailyLimit      = "{{getAmount($charges->daily_limit)}}";
            var currencyMonthlyLimit      = "{{getAmount($charges->monthly_limit)}}";

            if(currencyType == "CRYPTO"){
                var currencyPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
            }else{
                var currencyPrecison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
            }
            if(asking_rate_type == "CRYPTO"){
                var asking_rate_Precison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
            }else{
                var asking_rate_Precison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
            }

            return {
                currencyCode:currencyCode,
                currencyRate:currencyRate,
                selectedVal:selectedVal,
                currencyPrecison:currencyPrecison,

                asking_rate_code:asking_rate_code,
                asking_rate_value:asking_rate_value,
                asking_rate:asking_rate,
                asking_rate_Precison:asking_rate_Precison,

                currencyDailyLimit:currencyDailyLimit,
                currencyMonthlyLimit:currencyMonthlyLimit,
            };
        }



         // Get Fees
         function getFees() {

            var sender_currency = acceptVar().currencyCode;
            var sender_currency_rate = acceptVar().currencyRate;

            var ask_code = acceptVar().asking_rate_code;
            var ask_code = acceptVar().asking_rate;

            var charges = feesCalculation();
            if (charges == false) {
                return false;
            }

            var total_charge   = charges.total   == undefined ? 0 : charges.total * sender_currency_rate;
            var fixed_charge   = charges.fixed   == undefined ? 0 : charges.fixed * sender_currency_rate;

            var percent_charge = charges.percent == undefined ? 0 : charges.percent;
            var total_percent = charges.total_percent == undefined ? 0 : charges.total_percent;
            var min_limit = charges.min_limit == undefined ? 0 : charges.min_limit * sender_currency_rate;
            var max_limit = charges.max_limit == undefined ? 0 : charges.max_limit * sender_currency_rate;



            $('.fees-show').text('{{ __("charge") }}: '+ parseFloat(fixed_charge).toFixed(acceptVar().currencyPrecison) + ' ' +sender_currency+ ' + '+ parseFloat(total_percent).toFixed(acceptVar().currencyPrecison) + '%');
            $('.limit-show').text(parseFloat(min_limit).toFixed(acceptVar().currencyPrecison) + ' ' +sender_currency+ ' - '+ parseFloat(max_limit).toFixed(acceptVar().currencyPrecison) + ' ' + sender_currency);
        }


        function getDailyMonthlyLimit(){
            var sender_currency = acceptVar().currencyCode;
            var sender_currency_rate = acceptVar().currencyRate;

            var daily_limit = acceptVar().currencyDailyLimit;
            var monthly_limit = acceptVar().currencyMonthlyLimit

            if($.isNumeric(daily_limit) && $.isNumeric(monthly_limit)) {
                if(daily_limit > 0 ){
                    var daily_limit_calc = parseFloat(daily_limit * sender_currency_rate).toFixed(acceptVar().currencyPrecison);
                    $('.limit-daily').html( daily_limit_calc + " " + sender_currency);
                }else{
                    $('.limit-daily').html("");
                }

                if(monthly_limit > 0 ){
                    var montly_limit_clac = parseFloat(monthly_limit * sender_currency_rate).toFixed(acceptVar().currencyPrecison);
                    $('.limit-monthly').html( montly_limit_clac + " " + sender_currency);

                }else{
                    $('.limit-monthly').html("");
                }

            }else {
                $('.limit-daily').html("--");
                $('.limit-monthly').html("--");
                return {
                    dailyLimit:0,
                    monthlyLimit:0,
                };
            }

        }

        // Fees Calculation

        function feesCalculation(){
            var currency_rate = acceptVar().currencyRate;
            var sender_amount = $("input[name=amount]").val();
            var sender_amount_cal = (sender_amount/currency_rate);

            var total_charge;
            var fixed_charge_calc;
            var percent_charge_calc;
            var total_percent;

            let intervals = JSON.parse($('.intervals').attr("data-intervals"));

            var min_limit;
            var max_limit;
            var n = intervals.length;
            var counter = 1
            // console.log("Type of intervals:", typeof intervals);


            $.each(intervals, function (key, value) {

                if(counter == 1){
                    min_limit = value.min_limit;
                }
                if(key == n - 1){
                    max_limit = value.max_limit;
                }
                max_limit = value.max_limit;
                if (value.min_limit <= sender_amount_cal && value.max_limit >= sender_amount_cal) {
                    fixed_charge_calc   = value.charge;
                    percent_charge_calc = (parseFloat(sender_amount_cal) * parseFloat(value.percent)) / 100;
                    total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
                    total_percent = value.percent
                }
                counter++;
            });

            return {
                total: total_charge,
                fixed: fixed_charge_calc,
                percent: percent_charge_calc,
                total_percent: total_percent,
                min_limit: min_limit,
                max_limit: max_limit
            };
        }

        function getPreview() {
            var senderAmount = $("input[name=amount]").val();

            var rate_amount = $(".rate_amount").val();

            var sender_currency = acceptVar().currencyCode;
            var currency_rate = acceptVar().currencyRate;
            var asking_rate_code = acceptVar().asking_rate_code;

            // rate_amount
            senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;


            // // subtotal = (senderAmount / currency_rate);

            var charges = feesCalculation();
            var total_charge = charges.total == undefined ? 0 : charges.total;
            var fixed_charge   = charges.fixed;
            var percent_charge = charges.percent;
            var totalCharge =currency_rate*total_charge;
            var pay_total = parseFloat(totalCharge) + parseFloat(senderAmount);

            var will_pay = isNaN(pay_total) ? 0 : pay_total;
            var rate = rate_amount == "" ? 0 : rate_amount;

            let exchange_rate =  rate / (senderAmount == 0 ? currency_rate : senderAmount);
            exchange_rate = exchange_rate == '' ? 0 : exchange_rate;
            exchange_rate = parseFloat(exchange_rate).toFixed(10);

            // Sending Amount
            $('.request_amount').text(parseFloat(senderAmount).toFixed(acceptVar().currencyPrecison) + " " + sender_currency);
            $('.exchange_rate').text("1 "+ sender_currency + " = " + parseFloat(exchange_rate).toFixed(acceptVar().asking_rate_Precison) +" "+ asking_rate_code);
            $('.total_fees').text(parseFloat(totalCharge).toFixed(acceptVar().currencyPrecison)+ ' ' + sender_currency);
            $('.buyer_will_pay').text(parseFloat(rate).toFixed(acceptVar().asking_rate_Precison) + " " + asking_rate_code);
            $('.will_pay').text(parseFloat(will_pay).toFixed(acceptVar().currencyPrecison) + " " + sender_currency);
        }


        function get_remaining_limits(){
            var csrfToken           = $('meta[name="csrf-token"]').attr('content');
            var user_field          = "user_id";
            var user_id             = "{{ userGuard()['user']->id }}";
            var transaction_type    = "{{ payment_gateway_const()::TRADE }}";
            var currency_id         = acceptVar().selectedVal.data('id');
            var sender_amount       = $("input[name=sender_amount]").val();

            (sender_amount == "" || isNaN(sender_amount)) ? sender_amount = 0 : sender_amount = sender_amount;

            var charge_id           = "{{ $charges->id }}";
            var attribute           = "{{ payment_gateway_const()::SEND }}"

            $.ajax({
                type: 'POST',
                url: "{{ route('global.get.total.transactions') }}",
                data: {
                    _token:             csrfToken,
                    user_field:         user_field,
                    user_id:            user_id,
                    transaction_type:   transaction_type,
                    currency_id:        currency_id,
                    sender_amount:      sender_amount,
                    charge_id:          charge_id,
                    attribute:          attribute,
                },
                success: function(response) {
                    var sender_currency = acceptVar().currencyCode;

                    var status  = response.status;
                    var message = response.message;
                    var amount_data = response.data;

                    if(status == false){
                        $('.transfer').attr('disabled',true);
                        $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " + sender_currency);
                        $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " + sender_currency);
                        throwMessage('error',[message]);
                        return false;
                    }else{
                        $('.transfer').attr('disabled',false);
                        $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " + sender_currency);
                        $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " + sender_currency);
                    }
                },
            });
        }



    </script>
@endpush

