@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 300px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 256px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Money Out")])
@endsection

@section('content')
    <form action="{{ setRoute('admin.payment.gateway.store',['receiving-method','manual']) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="custom-card">
            <div class="card-header">
                <h6 class="title">
                    @isset($title)
                        {{ __($title) }}
                    @endisset
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-10-none">
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input-file',[
                            'label'             => __("Gateway Image"),
                            'class'             => "file-holder",
                            'name'              => "image",
                        ])
                    </div>
                    <div class="col-xl-9 col-lg-9">
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __('Gateway Name')."*",
                                'name'          => "gateway_name",
                                'placeholder'   => "ex: Paypal",
                                'value'         => old("gateway_name"),
                                'data_limit'    => 60,
                                'attribute'     => "required",
                            ])
                        </div>
                        <div class="form-group">
                             <label>{{ __("Currency Name*") }}</label>
                             <select class="form--control select2-auto-tokenize currency_name" name="currency_name">
                                 <option selected disabled value="">{{ __('Select Currency') }}</option>
                                 @foreach ($currencies as $item)
                                    <option value="{{ $item->name }}" data-currency-code="{{ $item->code }}" data-currency-symbol="{{ $item->symbol }}">{{ $item->name }}</option>
                                 @endforeach
                             </select>
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __('Currency Code')."*",
                                'name'          => "currency_code",
                                'placeholder'   => "ex: USD",
                                'value'         => old("currency_code"),
                                'class'         => "currency_type",
                                'data_limit'    => 8,
                                'attribute'     => "required",
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __('Currency Symbol')."*",
                                'name'          => "currency_symbol",
                                'placeholder'   => "ex: $",
                                'value'         => old("currency_symbol"),
                                'data_limit'    => 10,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="custom-card mt-15">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input-text-rich',[
                            'label'     => __('Instruction')."*",
                            'name'      => "desc",
                            'value'     => old("desc"),
                        ])
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.payment-gateway.manual.input-field-generator')
                    </div>
                </div>
                <div class="row mb-10-none">
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => "Add",
                            'permission'    => "admin.payment.gateway.store",
                        ])
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection

@push('script')
    <script>
         // Country Field On Change
         $(document).on("change",".currency_name",function() {
            var selectedValue = $(this);
            var currencyCode = $(".currency_name :selected").attr("data-currency-code");
            var currencySymbol = $(".currency_name :selected").attr("data-currency-symbol");

            selectedValue.parents("form").find("input[name=currency_symbol],input[name=currency_symbol]").val(currencySymbol).prop("readonly",true);
            selectedValue.parents("form").find("input[name=currency_code],input[name=currency_code]").val(currencyCode).prop("readonly",true);
            selectedValue.parents("form").find(".currency").text(currencyCode);
        });
    </script>
@endpush
