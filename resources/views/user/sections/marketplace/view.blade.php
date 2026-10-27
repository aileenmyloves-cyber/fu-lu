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
                <h3 class="title">{{ __("Marketplace") }}</h3>
                <button class="finter-btn"><i class="las la-sort-amount-down"></i></button>
            </div>
        </div>

        <div class="marketplace-wrapper mt-30">
            <div class="row mb-30-none">
                <div class="col-xxl-3 col-lg-4 col-md-6 col-sm-6 mb-30">
                    <div class="marketplace-item">
                        <div class="market-place-details">
                            <div class="top-wrapper">
                                <div class="thumb-area">
                                    <img src="{{ $trade->user->userImage }}" alt="thumb">
                                </div>
                                <h3 class="title">{{ @$trade->user->fullname }}<span class="badge"><i class="las la-certificate"></i></span></h3>
                            </div>
                            <div class="bottom-wrapper">
                                <span class="base-amount">1 {{ @$trade->saleCurrency->symbol }} = {{ get_amount((@$trade->rate / @$trade->amount), $trade->rateCurrency->symbol , get_wallet_precision($trade->rateCurrency)) }} </span>
                                <h4 class="amount-title">{{ get_amount(@$trade->amount, @$trade->saleCurrency->code, get_wallet_precision($trade->saleCurrency)) }}</h4>
                                <span class="divider"><i class="las la-exchange-alt"></i></span>
                                <h4 class="amount-title">{{ get_amount(@$trade->rate, @$trade->rateCurrency->code, get_wallet_precision($trade->rateCurrency)) }}</h4>
                            </div>
                        </div>
                        <div class="btn-area">
                            <a href="" class="btn--base btn-counter make_offer_button" data-trade="{{ json_encode($trade) }}" data-bs-toggle="modal" data-bs-target="#exampleModal">{{ __('Counter') }}</a>
                            <a href="{{ setRoute('user.marketplace.preview', $trade->id) }}" class="btn--base">{{ __('Buy') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>


    <div class="modal fade" id="offerCounterModal" tabindex="-1" aria-labelledby="offerCounterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content counter-modal">
            <div class="modal-header">
                <h5 class="title"><i class="fas fa-sync title-icon me-1"></i> {{ __('Make Counter Offer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="card-form" id="offer_form" action="{{ setRoute('user.trade.offer.submit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="OFFER">
                    <input type="hidden" name="receiver_id" value="">
                    <input type="hidden" name="creator_id" value="">
                    <input type="hidden" name="trade_id" value="">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __('Amount') }}</label>
                            <div class="input-group">
                                <input type="text" name="amount" class="form--control" placeholder="" disabled required>
                                <div class="input-group-append">
                                    <span class="input-group-text copytext sale_currency"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <div class="note-area">
                                <code class="d-block amount_text">--</code>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __('Rate') }}</label>
                            <div class="input-group">
                                <input type="text" name="rate" class="form--control" placeholder="" required>
                                <div class="input-group-append">
                                    <span class="input-group-text copytext rate_curency"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <div class="note-area">
                                <code class="d-block rate_text">--</code>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <button type="submit" class="btn--base w-100 btn-loading">{{ __('Send Now') }}</button>
                        </div>
                    </div>
                </form>
            </div>
          </div>
        </div>
    </div>

@endsection

@push('script')
<script>
    // filter
        $(".finter-btn").click(function () {
            $(".filter-form").slideToggle();
            $(".finter-btn").toggleClass("active");
        });
</script>

<script>



    $("select").niceSelect();
        var offerModal = new bootstrap.Modal(document.getElementById("offerCounterModal"), {});
        @if (Session::has('modal'))
            document.onreadystatechange = function () {
                offerModal.show();
            };
        @endif

        $(document).on('click', '.make_offer_button', function(e){
        e.preventDefault();




        var trade =  JSON.parse($(this).attr('data-trade'));
        var saleCurrencyType = trade.sale_currency.type;
        var rateCurrencyType = trade.rate_currency.type;

        if(saleCurrencyType == "CRYPTO"){
            var senderPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
        }else{
            var senderPrecison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
        }
        if(rateCurrencyType == "CRYPTO"){
            var receiverPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
        }else{
            var receiverPrecison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
        }

        $('#offer_form input[name="trade_id"]').val(trade.id);
        $('#offer_form input[name="amount"]').val(parseFloat(trade.amount).toFixed(senderPrecison));
        $('#offer_form .amount_text').text('{{ __("Amount") }} : '+ parseFloat(trade.amount).toFixed(senderPrecison) + ' ' + trade.sale_currency.code);
        $('#offer_form .rate_text').text('{{ __("Rate") }} : '+ parseFloat(trade.rate).toFixed(receiverPrecison) + ' ' + trade.rate_currency.code);
        $('#offer_form input[name="currency"]').val(trade.currency_id);
        $('#offer_form input[name="rate_currency"]').val(trade.rate_currency_id);
        $('#offer_form .sale_currency').text(trade.sale_currency.code);
        $('#offer_form .rate_curency').text(trade.rate_currency.code);
        $('#offer_form input[name="rate"]').val(parseFloat(trade.rate).toFixed(receiverPrecison));

        $('#offerCounterModal').modal('show');
    })







    $(".slider-range").slider({
        range: true,
        min: 12.00,
        max: 15000.00,
        values: [12.00, 15000.00],
        slide: function (event, ui) {
            $(".amount").val("$" + ui.values[0] + " - $" + ui.values[1]);
            $('input[name=min_price]').val(ui.values[0]);
            $('input[name=max_price]').val(ui.values[1]);
        },
        change: function () {
            var brand = [];
            var min = $('input[name="min_price"]').val();
            var max = $('input[name="max_price"]').val();
            $('.brand-filter input:checked').each(function () {
                brand.push(parseInt($(this).attr('value')));
            });

            var category_id = $(document).find('.filter-category li a.active').data('id');
        }
    });

    $(".amount").val("$" + $(".slider-range").slider("values", 0) + " - $" + $(".slider-range").slider("values", 1));

</script>

@endpush

