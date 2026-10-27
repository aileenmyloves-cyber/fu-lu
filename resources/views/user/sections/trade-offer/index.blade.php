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
    <div class="table-area mt-10">
        <div class="table-wrapper">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Client Offer') }}</h4>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __('Image (User)') }}</th>
                            <th>{{ __('Creator') }}</th>
                            <th>{{ __('TRX ID') }}</th>
                            <th>{{__('Selling Amount')}}</th>
                            <th>{{ __('Offer Amount') }}</th>
                            <th>{{ __('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($get_offers ?? [] as $item)

                            <tr>
                                <td>
                                    <div class="avatar-profile">
                                        <img src="{{ @$item->creator->userImage }}" alt="product">
                                    </div>
                                </td>
                                <td><span class="text--info">{{ $item->creator->fullName }}</span></td>
                                <td>{{ @$item->trades->transaction->trx_id }}</td>
                                <td> {{ get_amount($item->trades->amount, $item->trades->saleCurrency->code,get_wallet_precision($item->trades->saleCurrency->currency) )}} @ {{ get_amount($item->trades->rate, $item->trades->rateCurrency->code,get_wallet_precision($item->trades->rateCurrency->currency)) }}</td>
                                <td><span class="text--base">{{ get_amount($item->amount, $item->saleCurrency->code,get_wallet_precision($item->saleCurrency->currency)) }} @ {{ get_amount($item->rate, $item->trades->rateCurrency->code,get_wallet_precision($item->trades->rateCurrency->currency)) }} </span></td>

                                <td>
                                    <div class="offer-btn-area d-flex justify-content-end align-items-center gap-1 text-nowrap">
                                        @if ($item->trades->status == 1)
                                            @if (Auth::user()->id == $item->receiver_id)
                                                @if ($item->status == 4)
                                                    <button class="btn bg--danger reject offer-btn">{{ __('Rejected') }}</button>
                                                @elseif ($item->status == 1)
                                                    @if (Auth::user()->id == $item->trade_user_id)
                                                        <button class="btn btn--base accept offer-btn">{{ __('Accepted') }}</button>
                                                    @else
                                                        <a href="{{ setRoute('user.trade.offer.preview', $item->id) }}" class="btn btn--base accept offer-btn">{{ __('Pay') }}</a>
                                                    @endif
                                                @else
                                                    @if ($get_offers->where('receiver_id',Auth::user()->id)->first()->id == $item->id)
                                                        <a href="" class="btn bg--danger reject status-button offer-btn" data-id="{{ $item->id }}" data-title="{{ __('Reject') }}">{{ __('Reject') }}</a>
                                                        <button class="counter-btn make_offer_button btn bg--warning counter offer-btn"  data-trade="{{ json_encode($item) }}" data-bs-toggle="modal" data-bs-target="#counter-offer">{{ __('Counter Offer') }}</button>
                                                        <button class="btn btn--base accept status-button offer-btn" data-id="{{ $item->id }}" data-title="{{ __('Accept') }}">{{ __('Accept') }}</button>
                                                    @endif
                                                @endif
                                            @else
                                                @if ($item->status == 4)
                                                    <button class="btn bg--danger reject offer-btn">{{ __('Rejected') }}</button>
                                                @elseif ($item->status == 1)
                                                    @if (Auth::user()->id == $item->trade_user_id)
                                                        <button class="btn btn--base accept offer-btn">{{ __('Accepted') }}</button>
                                                    @else
                                                        <a href="{{ setRoute('user.trade.offer.preview', $item->id) }}" class="btn btn--base accept offer-btn">{{ __('Pay') }}</a>
                                                    @endif
                                                @endif
                                            @endif
                                        @else
                                            <button class="btn btn--base reject offer-btn">{{ __('Sold') }}</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center"><span class="text-danger">{{ __('No Offer Found') }}</span></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <nav>
            {{ $get_offers->links() }}
        </nav>
    </div>
</div>


<div class="modal fade" id="offerCounterModal" tabindex="-1" aria-labelledby="offerCounterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content counter-modal">
        <div class="modal-header">
            <h5 class="title mb-0"><i class="fas fa-sync title-icon me-1"></i> {{ __('Make Counter Offer') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form class="card-form" id="offer_form" action="{{ setRoute('user.trade.offer.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="COUNTER_OFFER">
                <input type="hidden" name="receiver_id" value="">
                <input type="hidden" name="creator_id" value="">
                <input type="hidden" name="trade_id" value="">
                <div class="row">
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __('Amount') }}</label>
                        <div class="input-group">
                            <input type="number" name="amount" class="form--control" placeholder="" disabled required>
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
                            <input type="number" name="rate" class="form--control" placeholder="" required>
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
      $(document).ready(function () {
        // Offer modal show
        $(document).on('click', '.make_offer_button', function(e){
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

            $('#offer_form input[name="trade_id"]').val(trade.trade_id);
            $('#offer_form input[name="receiver_id"]').val(trade.receiver_id);
            $('#offer_form input[name="creator_id"]').val(trade.creator_id);
            $('#offer_form input[name="amount"]').val(parseFloat(trade.amount).toFixed(senderPrecison));
            $('#offer_form .curency').text(trade.sale_currency.code);
            $('#offer_form .sale_currency').text(trade.sale_currency.code);
            $('#offer_form .rate_curency').text(trade.rate_currency.code);
            $('#offer_form input[name="rate"]').val(parseFloat(trade.rate).toFixed(receiverPrecison));
            $('#offer_form .amount_text').text('{{ __("Amount") }} : '+ parseFloat(trade.amount).toFixed(senderPrecison) + ' ' + trade.sale_currency.code);
            $('#offer_form .rate_text').text('{{ __("Rate") }} : '+ parseFloat(trade.rate).toFixed(receiverPrecison) + ' ' + trade.rate_currency.code);

            $('#offerCounterModal').modal('show');
        })

        $(".status-button").click(function(e){
            e.preventDefault();
            var actionRoute =  "{{ setRoute('user.trade.offer.status') }}";
            var target      = $(this).data('id');
            var title      = $(this).data('title');
            var message     = `{{ __('Are you sure to') }} <strong>`+title+`</strong>?`;
            openAlertModal(actionRoute,target,message,title,"POST");
        });
    });

</script>

@endpush

