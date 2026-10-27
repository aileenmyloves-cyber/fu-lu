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
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class=" d-flex justify-content-start">
                       <a href="{{ setRoute('user.cardyfie.virtual.card.create') }}" class="btn--base"><i class="las la-arrow-left"></i></a>
                    </div>
                    <div class="dash-payment-body">
                        <div class=" mt-20 ">
                            <form class="card-form row" action="{{ route('user.cardyfie.virtual.card.update.customer') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method("PUT")
                                <div class="p-3">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("first Name")."<span class='text--base'>*</span>",
                                                'placeholder'   => __("enter First Name"),
                                                'name'          => "first_name",
                                                'value'         => $card_customer->first_name
                                            ])
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("Last Name")."<span class='text--base'>*</span>",
                                                'placeholder'   => __("enter Last Name"),
                                                'name'          => "last_name",
                                                'value'         => $card_customer->last_name
                                            ])
                                        </div>
                                          <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("Date Of Birth")."<span class='text--base'>*</span>"." "."<span class='text--warning'> <small>(" . __("Should match with your ID") . ")</small></span>",
                                                'type'          => "date",
                                                'name'          => "date_of_birth",
                                                'value'         => old('date_of_birth', $card_customer->date_of_birth)
                                            ])
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Identity Type") }} <span class="text--base">*</span></label>
                                            <select class="form--control" name="identity_type" required>
                                                <option  disabled selected value="null" >{{ __('Choose One') }}</option>
                                                <option value="nid" {{ $card_customer->id_type == 'nid' ? 'selected' :'' }}>{{ __("National ID Card (NID)") }}</option>
                                                <option value="passport" {{ $card_customer->id_type == 'passport' ? 'selected' :'' }}>{{ __("Passport") }}</option>
                                                <option  value="bvn" {{ $card_customer->id_type == 'bvn' ? 'selected' :'' }}>{{ __("Bank Verification Number") }}</option>
                                            </select>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Identity Number") }} <span class="text--base">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form--control" required placeholder="{{__("Enter Identity Number")}}" name="identity_number" value="{{ old("identity_number",$card_customer->id_number) }}">
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input-file', [
                                                'label'         => __("ID Card Image (Font Side)")."<span class='text--base'>*</span>",
                                                'name'          => "id_front_image",
                                                'class'         => "form--control input-img",
                                            ])
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input-file', [
                                                'label'         => __("ID Card Image (Back Side)")."<span class='text--base'>*</span>",
                                                'name'          => "id_back_image",
                                                'class'         => "form--control input-img",
                                            ])
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input-file', [
                                                'label'         => __("Your Photo")."<span class='text--base'>*</span>",
                                                'name'          => "user_image",
                                                'class'         => "form--control input-img",
                                            ])
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("house Number")." "."<span class='text--base'>*</span>",
                                                'placeholder'   => __("Enter House Number"),
                                                'name'          => "house_number",
                                                'value'         => old('house_number',$card_customer->house_number)
                                            ])
                                        </div>


                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("city")." "."<span class='text--base'>*</span>",
                                                'placeholder'   => __("Enter City"),
                                                'name'          => "city",
                                                'value'         => old('city',$card_customer->city ?? "")
                                            ])
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("state")." "."<span class='text--base'>*</span>",
                                                'placeholder'   => __("Enter State"),
                                                'name'          => "state",
                                                'value'         => old('state', $card_customer->state ?? "")
                                            ])
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("Zip Code")." "."<span class='text--base'>*</span>",
                                                'placeholder'   => __("Enter Zip Code"),
                                                'name'          => "zip_code",
                                                'value'         => old('zip_code', $card_customer->zip_code ?? "")
                                            ])
                                        </div>
                                        <div class="col-xl-12 col-lg-12 form-group">
                                            @include('admin.components.form.textarea', [
                                                'label'         => __("address")." "."<span class='text--base'>*</span>",
                                                'placeholder'   => __("Enter Address"),
                                                'name'          => "address",
                                                'value'         => old('address',$card_customer->address_line_1 ?? "")
                                            ])
                                        </div>


                                    </div>
                                    <ul class="kyc-preview-wrapper">
                                        <li>
                                            <span class="label">{{ __("ID Card Image (Font Side)") }}:</span>
                                            <div class="thumb">
                                                <img src="{{ $card_customer->idFontImage ?? "" }}" alt="no-file">
                                            </div>
                                        </li>
                                        <li>
                                            <span class="label">{{__("ID Card Image (Back Side)") }}:</span>
                                            <div class="thumb">
                                                <img src="{{ $card_customer->idBackImage ?? "" }}" alt="no-file">
                                            </div>
                                        </li>
                                        <li>
                                            <span class="label">{{__("Your Photo") }}:</span>
                                            <div class="thumb">
                                                <img src="{{ $card_customer->userImage ?? "" }}" alt="no-file">
                                            </div>
                                        </li>
                                    </ul>
                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit" class="btn--base w-100 btn-loading">{{ __("Submit") }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

@endpush
