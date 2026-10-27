@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 280px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 246px !important;
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
    ], 'active' => __($page_title)])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" method="POST" enctype="multipart/form-data" action="{{ setRoute('admin.users.store') }}">
                @csrf
                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("first Name")."*",
                            'name'          => "firstname",
                            'placeholder'   =>  __("Write Here.."),
                            'value'         => old("firstname"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__("last Name")."*",
                            'name'          => "lastname",
                             'placeholder'   =>  __("Write Here.."),
                            'value'         => old("lastname"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__("Email")."*",
                            'name'          => "email",
                            'placeholder'   =>__("Enter Email"),
                            'value'         => old("email"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("country") }}<span>*</span></label>
                        <select name="country" class="form--control select2-auto-tokenize country-select" data-placeholder="{{ __('select Country') }}" data-old="{{ old('country') }}"></select>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("phone Number") }}<span>*</span></label>
                        <div class="input-group">
                            <div class="input-group-text phone-code">+</div>
                            <input class="phone-code" type="hidden" name="mobile_code" value="" />
                            <input type="text" class="form--control" placeholder="{{   __("Write Here..") }}" name="mobile" value="{{ old('mobile') }}">
                        </div>
                        @error("mobile")
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("Password") }}*</label>
                        <div class="input-group">
                            <input type="text" class="form--control place_random_password @error("password") is-invalid @enderror" placeholder="{{ __('Enter Password') }}" name="password">
                            <button class="input-group-text rand_password_generator" type="button">{{ __("Generate") }}</button>
                        </div>
                        @error("password")
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                     <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'         => __("Status"),
                            'value'         => old('status'),
                            'name'          => "status",
                            'options'       => [__("active") => 1, __("banned") => 0],
                            'permission'    => "admin.users.store",
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'         => __("email Verification"),
                            'value'         => old('email_verified'),
                            'name'          => "email_verified",
                            'options'       => [__("Verified") => 1, __("Unverified") => 0],
                            'permission'    => "admin.users.store",
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'         => __("SMS Verification"),
                            'value'         => old('sms_verified'),
                            'name'          => "sms_verified",
                            'options'       => [__("Verified") => 1, __("Unverified") => 0],
                            'permission'    => "admin.users.store",
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'     => __("KYC Verification"),
                            'value'     => old('kyc_verified'),
                            'name'      => "kyc_verified",
                            'options'       => [__("Verified") => 1, __("Unverified") => 0],
                            'permission'    => "admin.users.store",
                        ])
                    </div>
                     @if (admin_permission_by_name("admin.users.store"))
                        <div class="col-xl-12 col-lg-12 form-group pt-20">
                            <button type="submit" class="btn--base w-100 btn-loading">{{ __("Add") }}</button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@push("script")
    <script>
        function placeRandomPassword(clickedButton,placeInput) {
            $(clickedButton).click(function(){
                var generateRandomPassword = makeRandomString(10);
                $(placeInput).val(generateRandomPassword);
            });
        }
        placeRandomPassword(".rand_password_generator",".place_random_password");
    </script>
    <script>
        getAllCountries("{{ setRoute('global.countries.user') }}");
        $(document).ready(function() {

            openModalWhenError("email-send","#email-send");

            $("select[name=country]").change(function(){
                var phoneCode = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCode);
            });

            setTimeout(() => {
                var phoneCodeOnload = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCodeOnload);
            }, 400);
        });

    </script>
@endpush
