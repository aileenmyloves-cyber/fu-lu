@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@php
    // Once Strowallet accepted the card holder (customer_id exists) it can not be changed
    $card_holder_locked = !empty($customer_kyc->customer_id);
@endphp

@section('content')
<div class="body-wrapper">
    <div class="row justify-content-center">
        <div class="col-xl-8">
          <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="text-center">
                       <h4 class="text-muted">{{ $card_holder_locked ? __("Card Holder Information") : __("A cardholder is required before creating a card. Please create a cardholder first.") }}</h4>
                    </div>
                    <div class="dash-payment-body">
                        <div class=" mt-20 ">
                             <form class="card-form row g-4" action="{{ route('user.strowallet.virtual.card.store.customer') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <fieldset class="row g-4 m-0 p-0 border-0" @if($card_holder_locked) disabled style="pointer-events:none;" @endif>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>{{ __("name") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("Enter Name") }}" name="name" value="{{ old('name', $customer_kyc->name ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("first Name") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter First Name") }}" name="first_name" value="{{ old('first_name', $customer_kyc->first_name ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("last Name") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter Last Name") }}" name="last_name" value="{{ old('last_name', $customer_kyc->last_name ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("date Of Birth") }}<span>*</span></label>
                                        <input type="date" class="form--control" required placeholder="{{__("enter date Of Birth")}}" name="dob" value="{{ old('dob', optional($customer_kyc->dob ?? null)->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("ID Type") }}<span>*</span></label>
                                        <select class="form--control nice-select" name="id_type" required>
                                            <option value="">{{ __("Select ID Type") }}</option>
                                            <option value="national_id" {{ old('id_type', $customer_kyc->id_type ?? '') == 'national_id' ? 'selected' : '' }}>{{ __("National ID") }}</option>
                                            <option value="passport" {{ old('id_type', $customer_kyc->id_type ?? '') == 'passport' ? 'selected' : '' }}>{{ __("Passport") }}</option>
                                            <option value="drivers_license" {{ old('id_type', $customer_kyc->id_type ?? '') == 'drivers_license' ? 'selected' : '' }}>{{ __("Driver's License") }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("id Number") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter Id Number") }}" name="id_number" value="{{ old('id_number', $customer_kyc->id_number ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Email") }}<span>*</span></label>
                                        <input type="email" class="form--control" placeholder="{{ __("enter Email Address") }}" name="email" value="{{ old('email', $customer_kyc->email ?? auth()->user()->email) }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Address Line 1") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter Address") }}" name="line1" value="{{ old('line1', $customer_kyc->line1 ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("city") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter City") }}" name="city" value="{{ old('city', $customer_kyc->city ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("state") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter State") }}" name="state" value="{{ old('state', $customer_kyc->state ?? "") }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Zip/Postal") }}<span>*</span></label>
                                        <input type="text" class="form--control" placeholder="{{ __("enter Zip Code") }}" name="postal_code" value="{{ old('postal_code', $customer_kyc->postal_code ?? "") }}" required>
                                    </div>
                                </div>



                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("country") }}<span>*</span></label>
                                        <select name="country"class="form-control select2-auto-tokenize country-select select2-basic trx-type-select">
                                            <option value="" disabled selected>{{ __("select Country") }}</option>
                                            @foreach (get_all_countries() ?? [] as $country)
                                                <option value="{{ $country->iso3 }}" {{ old('country', $customer_kyc->country ?? '') == $country->iso3 ? 'selected' : '' }} data-mobile-code="{{  $country->mobile_code }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>


                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Phone") }}<span>*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-text phone-code">+{{ $customer_kyc->phone_code ?? auth()->user()->mobile_code }}
                                            </div>
                                            <input class="phone-code" type="hidden" name="phone_code"
                                                value="{{ $customer_kyc->phone_code ?? auth()->user()->mobile_code }}" />
                                            <input type="text" class="form--control"
                                                placeholder="{{ __('enter Phone Number') }}" name="phone"
                                                value="{{ old('phone', $customer_kyc->phone ?? auth()->user()->mobile) }}"
                                                >
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Account Purpose") }}<span>*</span></label>
                                        <select class="form--control nice-select" name="account_purpose" required>
                                            <option value="">{{ __("Select Account Purpose") }}</option>
                                            @foreach (\App\Models\StrowalletCustomerKyc::ACCOUNT_PURPOSES as $value => $text)
                                                <option value="{{ $value }}" {{ old('account_purpose', $customer_kyc->account_purpose ?? '') == $value ? 'selected' : '' }}>{{ __($text) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Employment Status") }}<span>*</span></label>
                                        <select class="form--control nice-select" name="employment_status" required>
                                            <option value="">{{ __("Select Employment Status") }}</option>
                                            @foreach (\App\Models\StrowalletCustomerKyc::EMPLOYMENT_STATUSES as $value => $text)
                                                <option value="{{ $value }}" {{ old('employment_status', $customer_kyc->employment_status ?? '') == $value ? 'selected' : '' }}>{{ __($text) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __("Occupation") }}<span>*</span></label>
                                        <select class="form--control nice-select" name="occupation" required>
                                            <option value="">{{ __("Select Occupation") }}</option>
                                            @foreach (\App\Models\StrowalletCustomerKyc::OCCUPATIONS as $value => $text)
                                                <option value="{{ $value }}" {{ old('occupation', $customer_kyc->occupation ?? '') == $value ? 'selected' : '' }}>{{ __($text) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xl-12 col-lg-12 form-group">
                                    @include('admin.components.form.input-file',[
                                        'label'             => __("appLIdImage"),
                                        'name'              => "id_image",
                                        'class'             => "file-holder",
                                        'old_files_path'    => files_asset_path('card-kyc-images'),
                                        'old_files'         => $customer_kyc->id_image ?? '',
                                    ])
                                </div>


                                </fieldset>

                                @if(!$card_holder_locked)
                                <div class="col-lg-12">
                                    <div class="button pt-3">
                                        <button type="submit" class="btn btn--base w-100 btn-loading buyBtn">{{ __("Confirm") }}</button>
                                    </div>
                                </div>
                                @endif
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
    <script>
        $(document).ready(function() {
            $("select[name=country]").change(function() {
                var phoneCode = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCode);
            });

        });

    </script>
@endpush
