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
    <div class="deposit-wrapper ptb-50">
         @if(isset($card_customer))
            @if(isset($card_customer->status) && $card_customer->status ==  global_const()::CARD_CUSTOMER_APPROVED_STATUS)
                <div class="dashboard-header-wrapper d-flex justify-content-end">
                    <a href="{{ setRoute('user.cardyfie.virtual.card.edit.customer') }}" class="btn--base text-end">{{ __("Update Customer") }}</a>
                </div>
            @endif
        @endif
        <div class="row justify-content-center">
            {{-- create card customer  --}}
            @if($card_customer == null)
                @include('user.sections.virtual-card-cardyfie.component.create-customer')
            @endif
            {{-- check and update for customer  --}}
            @if(isset($card_customer) )
                @if(isset($card_customer->status) && $card_customer->status ==  global_const()::CARD_CUSTOMER_PENDING_STATUS || $card_customer->status ==  global_const()::CARD_CUSTOMER_REJECTED_STATUS)
                    @include('user.sections.virtual-card-cardyfie.component.check-customer-status')
                @endif
            @endif
            {{-- Create card  --}}
            @if(isset($card_customer))
                @if(isset($card_customer->status) && $card_customer->status ==  global_const()::CARD_CUSTOMER_APPROVED_STATUS)
                    @include('user.sections.virtual-card-cardyfie.component.create-card')
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')

@endpush
