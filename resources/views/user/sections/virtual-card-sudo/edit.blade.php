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
                    <div class=" d-flex justify-content-center">
                        <h4 class="text-center">
                            {{ __("Please review the customer details carefully. Update only the fields that need to be changed.") }}
                        </h4>
                    </div>
                    <div class="d-flex justify-content-center">
                          <h4>{{ __("Current Status") }}: <span class="{{  $customer->status == 'active' ? 'badge badge--success' : 'badge badge--warning'}}">{{ strtoupper($customer->status) }}</span> </h4>
                    </div>
                    <div class="dash-payment-body">
                        <div class=" mt-20 ">
                            <form class="card-form row" action="{{ route('user.sudo.virtual.card.update.customer') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method("PUT")

                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label' => __("first Name") . "<span class='text--base'>*</span>",
                                                'placeholder'   => __("enter First Name"),
                                                'name'          => "first_name",
                                                'value'         => $customer->individual->firstName
                                            ])
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            @include('admin.components.form.input', [
                                                'label'         => __("last Name"). "<span class='text--base'>*</span>",
                                                'placeholder'   => __("enter Last Name"),
                                                'name'          => "last_name",
                                                'value'         => $customer->individual->lastName
                                            ])
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("date Of Birth") }} <span class="text--base">*</span></label>
                                            <div class="input-group">
                                                <input type="date" class="form--control" required placeholder="{{__("enter date Of Birth")}}" name="date_of_birth" value="{{ \Carbon\Carbon::parse($customer->individual->dob)->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Identity Type") }} <span class="text--base">*</span></label>
                                            <select class="form--control" name="identity_type" required>
                                                <option  disabled selected value="null" >{{ __('Choose One') }}</option>
                                                <option  value="BVN" {{ $customer->individual->identity->type ==  "BVN" ? 'selected' : '' }}>{{ __("Bank Verification Number") }}</option>
                                                <option  value="NIN" {{$customer->individual->identity->type ==  "NIN" ? 'selected' : ''}}>{{ __("National Identification Number") }}</option>
                                                <option  value="TIN" {{$customer->individual->identity->type ==  "TIN" ? 'selected' : '' }}>{{ __("Taxpayer identification numbers") }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Identity Number") }} <span class="text--base">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form--control" required placeholder="{{__("Enter Identity Number")}}" name="identity_number" value="{{ old('identity_number', $customer->individual->identity->number ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Phone") }}<span class="text--base">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form--control prepend" required placeholder="{{ __('enter Phone Number') }}" name="phone_number" value="{{ old('phone_number',$customer->phoneNumber) }}" />
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Address Line 1") }} <span class="text--base">*</span></label>
                                            <input type="text" class="form--control" required
                                                name="line1"
                                                placeholder="{{ __('Enter Address Line 1') }}"
                                                value="{{ old('line1', $customer->billingAddress->line1 ?? '') }}">
                                        </div>
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("WebAddress Line 2") }}</label>
                                            <input type="text" class="form--control"
                                                name="line2"
                                                placeholder="{{ __('Enter Address Line 2') }}"
                                                value="{{ old('line2', $customer->billingAddress->line2 ?? '') }}">
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("city") }} <span class="text--base">*</span></label>
                                            <input type="text" class="form--control" required
                                                name="city"
                                                placeholder="{{ __('enter City') }}"
                                                value="{{ old('city', $customer->billingAddress->city ?? '') }}">
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("state") }} <span class="text--base">*</span></label>
                                            <input type="text" class="form--control" required
                                                name="state"
                                                placeholder="{{ __('enter State') }}"
                                                value="{{ old('state', $customer->billingAddress->state ?? '') }}">
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("select Country") }} <span class="text--base">*</span></label>
                                            <select class="form--control select2-basic" name="country" required>
                                                <option  disabled selected value="null" >{{ __('Choose One') }}</option>
                                                @foreach(get_all_countries() ?? [] as $key => $country)
                                                    <option  value="{{ $country->name }}" {{ $customer->billingAddress->country ==  $country->name  ? 'selected' : ''}}>{{ $country->name }}</option>
                                                @endforeach

                                            </select>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label>{{ __("Postal Code") }} <span class="text--base">*</span></label>
                                            <input type="text" class="form--control" required
                                                name="postal_code"
                                                placeholder="{{ __('Enter Postal Code') }}"
                                                value="{{ old('postal_code', $customer->billingAddress->postalCode ?? '') }}">
                                        </div>

                                            {{-- ID CARD FRONT --}}
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label class="mw-100">
                                                {{ __("ID Card(Front Side)") }}
                                                <span class="text--base">*</span>
                                            </label>

                                            @php
                                                $idFront = $customer->individual->documents->idFrontUrl ?? null;
                                            @endphp

                                            {{-- Preview --}}
                                            <div class="mb-2">
                                                <img id="preview-id-front"
                                                    src="{{ $idFront ?? '' }}"
                                                    alt="ID Front"
                                                    style="width:100%; height:150px; object-fit:cover; border-radius:6px; {{ $idFront ? '' : 'display:none;' }}">
                                            </div>

                                            <input type="file" name="id_image_front"
                                                class="form--control d-none"
                                                id="idFrontInput">

                                            <button type="button" class="btn btn--base btn-sm"
                                                    onclick="document.getElementById('idFrontInput').click()">
                                                {{ $idFront ? __('Change Image') : __('upload Image') }} <i class="las la-edit me-1"></i>
                                            </button>
                                        </div>


                                        {{-- ID CARD BACK --}}
                                        <div class="col-xl-6 col-lg-6 form-group">
                                            <label class="mw-100">
                                                {{ __("ID Card(Back Side)") }}
                                                <span class="text--base">*</span>
                                            </label>

                                            @php
                                                $idBack = $customer->individual->documents->idBackUrl ?? null;
                                            @endphp

                                            <div class="mb-2">
                                                <img id="preview-id-back"
                                                    src="{{ $idBack ?? '' }}"
                                                    alt="ID Back"
                                                    style="width:100%; height:150px; object-fit:cover; border-radius:6px; {{ $idBack ? '' : 'display:none;' }}">
                                            </div>

                                            <input type="file" name="id_image_back"
                                                class="form--control d-none"
                                                id="idBackInput">

                                            <button type="button" class="btn btn--base btn-sm"
                                                    onclick="document.getElementById('idBackInput').click()">
                                                {{ $idBack ? __('Change Image') : __('upload Image') }} <i class="las la-edit me-1"></i>
                                            </button>
                                        </div>


                                        {{-- ADDRESS VERIFICATION --}}
                                        <div class="col-xl-12 col-lg-12 form-group">
                                            <label class="mw-100">
                                                {{ __("Address Verification") }}
                                                <span class="text--base">({{ __("Optional") }})</span>
                                            </label>

                                            @php
                                                $addressDoc = $customer->individual->documents->addressVerificationUrl ?? null;
                                            @endphp

                                            <div class="mb-2">
                                                <img id="preview-address"
                                                    src="{{ $addressDoc ?? '' }}"
                                                    alt="Address Verification"
                                                    style="width:100%; height:150px; object-fit:cover; border-radius:6px; {{ $addressDoc ? '' : 'display:none;' }}">
                                            </div>

                                            <input type="file" name="address_verification"
                                                class="form--control d-none"
                                                id="addressInput">

                                            <button type="button" class="btn btn--base btn-sm"
                                                    onclick="document.getElementById('addressInput').click()">

                                                {{ $addressDoc ? __('Change Image') : __('upload Image') }} <i class="las la-edit me-1"></i>
                                            </button>
                                        </div>

                                        <div class="col-xl-12 col-lg-12">
                                            <button type="submit" class="btn--base w-100 btn-loading buyBtn">{{ __("update") }} <i class="las la-user-edit ms-1"></i></button>
                                        </div>


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
<script>
    $(document).ready(function () {

        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();

                reader.onload = function (e) {
                    $(previewId).attr('src', e.target.result).show();
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $('#idFrontInput').on('change', function () {
            previewImage(this, '#preview-id-front');
        });

        $('#idBackInput').on('change', function () {
            previewImage(this, '#preview-id-back');
        });

        $('#addressInput').on('change', function () {
            previewImage(this, '#preview-address');
        });

    });
</script>

@endpush
