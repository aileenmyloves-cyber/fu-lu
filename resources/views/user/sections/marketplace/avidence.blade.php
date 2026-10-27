@php
    $default = get_default_language_code();
@endphp
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
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __($page_title) }}</h3>
        </div>
    </div>
    <div class="row justify-content-center mb-30-none">
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{!! $payment_gateway->desc !!}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <form action="{{ setRoute('user.marketplace.evidance.submit') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="trade_id" value="{{ $trade->id }}">
                            <input type="hidden" name="gateway_id" value="{{ $payment_gateway->id }}">
                            <div class="row">
                                @foreach ($payment_gateway->input_fields as $item)
                                    @if ($item->type == "select")
                                        <div class="col-lg-12 form-group">
                                            <label for="{{ $item->name }}">{{ __($item->label) }}</label>
                                            <select name="{{ $item->name }}" id="{{ $item->name }}" class="form--control nice-select">
                                                <option selected disabled>{{ __('Choose One') }}</option>
                                                @foreach ($item->validation->options as $innerItem)
                                                    <option value="{{ $innerItem }}">{{ $innerItem }}</option>
                                                @endforeach
                                            </select>
                                            @error($item->name)
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    @elseif ($item->type == "file")
                                        <div class="col-lg-12 form-group">
                                            @include('admin.components.form.input-dynamic',[
                                                'label'     => __($item->label),
                                                'name'      => $item->name,
                                                'type'      => $item->type,
                                                'no_error_message'      => 'no_error_message',
                                                // 'class'      => 'file-holder',
                                                'value'     => old($item->name),
                                            ])
                                        </div>
                                    @elseif ($item->type == "text")
                                        <div class="col-lg-12 form-group">
                                            @include('admin.components.form.input-dynamic',[
                                                'label'     => __($item->label),
                                                'name'      => $item->name,
                                                'type'      => $item->type,
                                                'no_error_message'      => 'no_error_message',
                                                'value'     => old($item->name),
                                            ])
                                        </div>
                                    @elseif ($item->type == "textarea")
                                        <div class="col-lg-12 form-group">
                                            @include('admin.components.form.textarea',[
                                                'label'     => __($item->label),
                                                'name'      => $item->name,
                                                'no_error_message'      => 'no_error_message',
                                                'value'     => old($item->name),
                                            ])
                                        </div>
                                    @endif
                                @endforeach

                                <div class="my-3 col-12">
                                    <button type="submit" class="btn--base w-100">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

@endpush
