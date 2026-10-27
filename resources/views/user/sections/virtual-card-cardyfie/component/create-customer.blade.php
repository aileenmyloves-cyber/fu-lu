<div class="col-xl-8">
    <div class="dash-payment-item-wrapper">
        <div class="dash-payment-item active">
            <div class="dash-payment-title-area">
                <span class="dash-payment-badge">!</span>
                <h5 class="title">{{ __("Create Card Customer") }}</h5>
            </div>
            <div class="dash-payment-body">
                <div class=" mt-20 ">
                    <form class="card-form row" action="{{ route('user.cardyfie.virtual.card.create.customer') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="p-3">
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("first Name")."<span class='text--base'>*</span>",
                                        'placeholder'   => __("enter First Name"),
                                        'name'          => "first_name",
                                        'value'         => old('first_name',$user->firstname)
                                    ])
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("last Name")."<span class='text--base'>*</span>",
                                        'placeholder'   => __("enter Last Name"),
                                        'name'          => "last_name",
                                        'value'         => old('last_name',$user->lastname)
                                    ])
                                </div>
                                {{-- <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Phone") }}<span class="text--base">*</span></label>

                                    <div class="input-group">
                                        <div class="input-group-text phone-code">+{{  $user->mobile_code??"" }}</div>
                                        <input type="text" class="form--control" placeholder="{{ __('enter Phone Number') }}" value="{{ $user->mobile??"" }}" readonly>
                                    </div>
                                </div> --}}
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Email") }}<span class="text--base">*</span></label>
                                    <input type="email" class="form--control" name="email" placeholder="{{ __('Enter Customer Email') }}" value="{{ old('customer_email',$user->email) }}"/>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("Date Of Birth")."<span class='text--base'>*</span>"." "."<span class='text--warning'> <small>(" . __("Should match with your ID") . ")</small></span>",
                                        'type'          => "date",
                                        'name'          => "date_of_birth",
                                        'value'         => old('date_of_birth')
                                    ])
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Identity Type") }} <span class="text--base">*</span></label>
                                    <select class="form--control" name="identity_type" required>
                                        <option  disabled selected value="null" >{{ __('Choose One') }}</option>
                                        <option value="nid">{{ __("National ID Card (NID)") }}</option>
                                        <option value="passport">{{ __("Passport") }}</option>
                                        <option  value="bvn">{{ __("Bank Verification Number") }}</option>
                                    </select>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Identity Number") }} <span class="text--base">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form--control" required placeholder="{{__("Enter Identity Number")}}" name="identity_number" value="{{ old("identity_number") }}">
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
                                        'value'         => old('house_number')
                                    ])
                                </div>

                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("country") }} <span class="text--base">*</span></label>
                                    <select class="form--control select2-basic" name="country" required>
                                        <option  disabled selected value="null" >{{ __('Select Country') }}</option>
                                        @foreach (get_all_countries() ?? [] as  $country)
                                            <option value="{{ $country->iso2 }}" {{ @$user->address->country   ==  $country->name ? 'selected' : ''}}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("city")." "."<span class='text--base'>*</span>",
                                        'placeholder'   => __("Enter City"),
                                        'name'          => "city",
                                        'value'         => old('city',$user->address->city ?? "")
                                    ])
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("state")." "."<span class='text--base'>*</span>",
                                        'placeholder'   => __("Enter State"),
                                        'name'          => "state",
                                        'value'         => old('state',$user->address->state ?? "")
                                    ])
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    @include('admin.components.form.input', [
                                        'label'         => __("Zip Code")." "."<span class='text--base'>*</span>",
                                        'placeholder'   => __("Enter Zip Code"),
                                        'name'          => "zip_code",
                                        'value'         => old('zip_code', $user->address->zip ?? "")
                                    ])
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    @include('admin.components.form.textarea', [
                                        'label'         => __("address")." "."<span class='text--base'>*</span>",
                                        'placeholder'   => __("Enter Address"),
                                        'name'          => "address",
                                        'value'         => old('address',$user->address->address ?? "")
                                    ])
                                </div>

                            </div>
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
