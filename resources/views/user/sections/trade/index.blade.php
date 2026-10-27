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
                    <h4 class="title">{{__(@$page_title)}}</h4>
                    <div class="dashboard-btn-wrapper">
                        <div class="dashboard-btn">
                            <a href="{{ setRoute('user.marketplace.index') }}" class="btn--base btn">{{__('Marketplace')}} </a>
                            <a href="{{ setRoute('user.trade.offer.index') }}" class="btn--base btn">{{__('Get Offer')}} </a>
                            <a href="{{ setRoute('user.trade.create') }}" class="btn--base btn"><i class="las la-plus me-1"></i> {{__('Create Trade')}} </a>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>{{ __('TRX ID') }}</th>
                                <th>{{ __('Selling Amount') }}</th>
                                <th>{{ __('Asking Amount') }}</th>
                                <th>{{ __('Exchange Rate') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $item)
                                <tr>
                                    <td>{{ $item->trx_id }}</td>
                                    <td>{{ get_amount($item->trade->amount, $item->trade->saleCurrency->code,get_wallet_precision($item->trade->saleCurrency)) }}</td>
                                    <td>{{ get_amount($item->trade->rate, $item->trade->rateCurrency->code,get_wallet_precision($item->trade->rateCurrency)) }}</td>
                                    <td>1 {{ $item->trade->saleCurrency->code }} = {{ get_amount(($item->trade->rate / $item->trade->amount), $item->trade->rateCurrency->code,get_wallet_precision($item->trade->rateCurrency)) }}</td>
                                    <td><span class="badge {{ $item->TradeStringStatus->class }}">{{ $item->TradeStringStatus->value }}</span></td>
                                    <td>{{ dateFormat('d M Y | h:i:s A', $item->created_at) }}</td>
                                    <td>
                                        <div class="offer-btn-area d-flex align-items-center justify-content-end gap-1">
                                            @if ($item->trade->status == 1)

                                                <a href="#0" title="Close" class="btn--danger status-button" data-id="{{ $item->id }}" data-title="{{ __('Close Trade') }}"> <i class="las la-times"></i></a>
                                                <a href="{{ setRoute('user.trade.edit', $item->trade->id) }}" title="Edit" class="btn btn--base"><i class="las la-pencil-alt"></i></a>

                                            @else

                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center"><span class="text-danger">{{ __('No Records Found') }}</span></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <nav>
                {{ $transactions->links() }}
            </nav>
        </div>


    </div>

@endsection

@push('script')
    <script>
        $(".status-button").click(function(e){
            e.preventDefault();
            var actionRoute = "{{ setRoute('user.trade.cancel') }}";
            var target      = $(this).data('id');
            var title       = $(this).data('title');
            var message     = `{{ __('Are you sure to') }} <strong>`+title+`</strong>?`;
            openAlertModal(actionRoute,target,message,title,"POST");
        });
    </script>
@endpush

