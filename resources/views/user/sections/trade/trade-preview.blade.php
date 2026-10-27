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
        <div class="row justify-content-center mt-30">
            <div class="col-xl-6">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Transaction Created!') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="content text-center mt-20">
                                <h3 class="title">{{ __('Trade Create Successfully') }}</h3>
                                <p>{{__('Your post will be approved once your funds have been received. You can share this URL or QR code with a potential buyer if you already have one!')}}</p>
                            </div>
                            <form class="card-form mt-20" action="dashboard.html">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="qr-code-thumb text-center">
                                            <img class="mx-auto" src="{{ $qrcode }}" alt="qr code">
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="input-group">
                                            <input type="text" class="form--control" id="copy_text" value="{{ $url }}" >
                                            <div class="input-group-text copy_button" style="cursor: pointer"><i class="las la-copy"></i></div>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12">
                                        <a href="{{ setRoute('user.trade.index') }}" class="btn--base w-100"><i class="las la-angle-left me-1"></i> {{ __('back To Home') }} </a>
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
   <script>
        $(document).ready(function () {
            $(document).on('click', '.copy_button', function(){
                copyText();
            })
        });

        function copyText(){
            var copyText = document.getElementById("copy_text");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            notification('success','Url Coppied Successfully');
        }
   </script>
@endpush
