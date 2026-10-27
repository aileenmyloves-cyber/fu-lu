@extends('admin.layouts.master')

@push('css')

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
    ], 'active' => __("Virtual Card Api")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("Virtual Card Api") }}</h6>
            <a href="https:\/\/cardyfie.co" target="_blank" class="btn--base d-none cardyfie-link">
                <i class="las la-link"></i>
                {{ __("Explore Card Issuing API - Cardyfie") }}
            </a>
        </div>
        <div class="card-body">

            <form class="card-form" action="{{ setRoute('admin.virtual.card.api.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method("PUT")
                <div class="row mb-10-none">
                    <div class="col-xl-12   col-lg-12 form-group">
                        <label>{{ __("name") }}*</label>
                        <select class="form--control nice-select" name="api_method">
                            <option disabled>{{ __("Select Platfrom") }}</option>
                            <option value = "strowallet" @if(@$api->config->name == 'strowallet') selected @endif>@lang('Strowallet Api')</option>
                            <option value = "sudo" @if(@$api->config->name == 'sudo') selected @endif>@lang('Sudo Africa')</option>
                            <option value = "stripe" @if(@$api->config->name == 'stripe') selected @endif>@lang('Stripe Api')</option>
                            <option value = "flutterwave" @if(@$api->config->name == 'flutterwave') selected @endif>@lang('Flutterwave')</option>
                            <option value = "cardyfie" @if(@$api->config->name == 'cardyfie') selected @endif>@lang('Cardyfie')</option>
                        </select>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="flutterwave">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="provider-account-balance" data-balance-url="{{ setRoute('admin.virtual.card.api.balance', 'flutterwave') }}">
                                    <div class="balance-card-head">
                                        <h6 class="title">{{ __("Flutterwave Balance") }}</h6>
                                        <button type="button" class="balance-toggle-btn"><i class="las la-eye"></i></button>
                                    </div>
                                    <div class="user-info d-none">
                                        <h2 class="user-count"><span class="balance-amount"></span></h2>
                                    </div>
                                    <div class="user-badge d-none"></div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="flutterwave_secret_key" value="{{ @$api->config->flutterwave_secret_key }}">
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Base URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="flutterwave_url" value="{{ @$api->config->flutterwave_url }}">
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="sudo">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="provider-account-balance" data-balance-url="{{ setRoute('admin.virtual.card.api.balance', 'sudo') }}">
                                    <div class="balance-card-head">
                                        <h6 class="title">{{ __("Sudo Balance") }}</h6>
                                        <button type="button" class="balance-toggle-btn"><i class="las la-eye"></i></button>
                                    </div>
                                    <div class="user-info d-none">
                                        <h2 class="user-count"><span class="balance-amount"></span></h2>
                                    </div>
                                    <div class="user-badge d-none"></div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 form-group">
                                <label>{{ __("api Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="sudo_api_key" value="{{ @$api->config->sudo_api_key }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Vault ID Url*") }}</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="sudo_vault_id" value="{{ @$api->config->sudo_vault_id }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Base URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="sudo_url" value="{{ @$api->config->sudo_url }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('sudo_mode',@$api->config->sudo_mode),
                                    'name'          => "sudo_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('sand Box') => global_const()::SANDBOX]
                                ])
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Webhook URL") }}</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>

                                    <input type="text" class="form--control referralURL"  value="{{ setRoute('user.sudo.virtual.card.handle.webhook') }}" readonly>
                                    <div class="input-group-text copytext" id="copyBoard"><i class="las la-copy"></i></div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="stripe">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="provider-account-balance" data-balance-url="{{ setRoute('admin.virtual.card.api.balance', 'stripe') }}">
                                    <div class="balance-card-head">
                                        <h6 class="title">{{ __("Stripe Issuing Balance") }}</h6>
                                        <button type="button" class="balance-toggle-btn"><i class="las la-eye"></i></button>
                                    </div>
                                    <div class="user-info d-none">
                                        <h2 class="user-count"><span class="balance-amount"></span></h2>
                                    </div>
                                    <div class="user-badge d-none"></div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="stripe_public_key" value="{{ @$api->config->stripe_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="stripe_secret_key" value="{{ @$api->config->stripe_secret_key }}">
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Base URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="stripe_url" value="{{ @$api->config->stripe_url }}">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('stripe_mode',@$api->config->stripe_mode),
                                    'name'          => "stripe_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group configForm" id="strowallet">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="provider-account-balance" data-balance-url="{{ setRoute('admin.virtual.card.api.balance', 'strowallet') }}">
                                    <div class="balance-card-head">
                                        <h6 class="title">{{ __("Strowallet Balance") }}</h6>
                                        <button type="button" class="balance-toggle-btn"><i class="las la-eye"></i></button>
                                    </div>
                                    <div class="user-info d-none">
                                        <h2 class="user-count"><span class="balance-amount"></span></h2>
                                    </div>
                                    <div class="user-badge d-none"></div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Public Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="strowallet_public_key" value="{{ @$api->config->strowallet_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="strowallet_secret_key" value="{{ @$api->config->strowallet_secret_key }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 form-group">
                                <label>{{ __("Base URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="strowallet_url" value="{{ @$api->config->strowallet_url }}">
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                <label>{{ __("Webhook URL") }}</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>

                                    <input type="text" class="form--control referralURL"  value="{{ setRoute('user.strowallet.virtual.card.webhook') }}" readonly>
                                    <div class="input-group-text copytext" id="copyBoard"><i class="las la-copy"></i></div>
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('strowallet_mode',@$api->config->strowallet_mode),
                                    'name'          => "strowallet_mode",
                                    'options'       => [__('Live') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>

                        </div>
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group configForm" id="cardyfie">
                        <div class="row" >
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="provider-account-balance">
                                    <h6 class="title">{{ __("Cardyfie Balance") }}</h6>
                                    <div class="user-info">
                                        <h2 class="user-count">{{ __("N/A") }}</h2>
                                    </div>
                                    <div class="user-badge">
                                        <span class="badge badge--success">{{ __("SuccessFully Fetch Account Balance") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("primary Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="cardyfie_public_key" value="{{ @$api->config->cardyfie_public_key }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("secret Key") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="cardyfie_secret_key" value="{{ @$api->config->cardyfie_secret_key }}">
                                </div>
                            </div>

                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Sandbox URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="cardyfie_sandbox_url" value="{{ @$api->config->cardyfie_sandbox_url ?? '' }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Production URL") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control" name="cardyfie_production_url" value="{{ @$api->config->cardyfie_production_url ?? '' }}">
                                </div>
                            </div>

                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Webhook Secret") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-key"></i></span>
                                    <input type="text" class="form--control" name="cardyfie_webhook_secret" value="{{ @$api->config->cardyfie_webhook_secret ?? '' }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Webhook URL") }}</label>
                                <div class="input-group append">
                                    <span class="input-group-text"><i class="las la-link"></i></span>
                                    <input type="text" class="form--control referralURL"  value="{{ setRoute('user.cardyfie.virtual.card.webhook') }}" readonly>
                                    <div class="input-group-text copytext" id="copyBoard"><i class="las la-copy"></i></div>
                                </div>
                            </div>

                             <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Universal Card Issue Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_universal_card_issues_fee" value="{{ get_amount($api->config->cardyfie_universal_card_issues_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>

                             <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Platinum Card Issue Fee") }}*</label>
                                <div class="input-group append">
                                    <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_platinum_card_issues_fee" value="{{ get_amount($api->config->cardyfie_platinum_card_issues_fee ?? 0,null,get_wallet_precision()) }}">

                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Card Deposit Fixed Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_card_deposit_fixed_fee" value="{{ get_amount($api->config->cardyfie_card_deposit_fixed_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Card Deposit Percent Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_card_deposit_percent_fee" value="{{ get_amount($api->config->cardyfie_card_deposit_percent_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Card Withdraw Fixed Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_card_withdraw_fixed_fee" value="{{ get_amount($api->config->cardyfie_card_withdraw_fixed_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Card Withdraw Percent Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_card_withdraw_percent_fee" value="{{ get_amount($api->config->cardyfie_card_withdraw_percent_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Card Maintenance Fixed Fee") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_card_maintenance_fixed_fee" value="{{ get_amount($api->config->cardyfie_card_maintenance_fixed_fee ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Minimum amount") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_min_limit" value="{{ get_amount($api->config->cardyfie_min_limit ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Maximum amount") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_max_limit" value="{{ get_amount($api->config->cardyfie_max_limit ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Daily Limit") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_daily_limit" value="{{ get_amount($api->config->cardyfie_daily_limit ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 form-group">
                                <label>{{ __("Monthly Limit") }}*</label>
                                <div class="input-group append">
                                   <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                    <input type="text" class="form--control number-input" name="cardyfie_monthly_limit" value="{{ get_amount($api->config->cardyfie_monthly_limit ?? 0,null,get_wallet_precision()) }}">
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-sm-12 form-group">
                                @include('admin.components.form.switcher', [
                                    'label'         => __('Mode'),
                                    'value'         => old('cardyfie_mode',@$api->config->cardyfie_mode),
                                    'name'          => "cardyfie_mode",
                                    'options'       => [__('Production') => global_const()::LIVE,__('Sandbox') => global_const()::SANDBOX]
                                ])
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__( 'Card Limit').'* ('.__("If Setup is 0, Card Creation is Unlimited").")",
                            'name'          => 'card_limit',
                            'value'         => old('card_limit',@$api->card_limit),
                            'placeholder'   => "Enter Limit.",
                            'class'   => "card-limit-label"

                        ])
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input-text-rich',[
                            'label'         => __("card Details")."*",
                            'name'          => 'card_details',
                            'value'         => old('card_details',@$api->card_details),
                            'placeholder'   => __( "Write Here..."),
                        ])
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label for="card-image">{{ __("Background Image") }}</label>
                        <div class="col-12 col-sm-6 m-auto">
                            @include('admin.components.form.input-file',[
                                'label'         => false,
                                'class'         => "file-holder m-auto",
                                'old_files_path'    => files_asset_path('card-api'),
                                'name'          => "image",
                                'old_files'         => old('image',@$api->image)
                            ])
                        </div>
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("update"),
                            'permission'    => "admin.virtual.card.api.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script')
    <script>
        (function ($) {
            "use strict";

            var method = '{{ @$api->config->name }}';
            if (!method) {
                method = 'flutterwave';
            }

            apiMethod(method);
            toggleCardyfieLink(method);
            updateCardLimitLabel(method);

            $('select[name=api_method]').on('change', function() {
                var method = $(this).val();
                apiMethod(method);
                toggleCardyfieLink(method);
            });

            function apiMethod(method) {
                $('.configForm').addClass('d-none');
                if (method !== 'other') {
                    $(`#${method}`).removeClass('d-none');
                }
                updateCardLimitLabel(method);
            }

            function toggleCardyfieLink(method) {
                if (method === 'cardyfie') {
                    $('.cardyfie-link').removeClass('d-none');
                } else {
                    $('.cardyfie-link').addClass('d-none');
                }
            }

            function updateCardLimitLabel(method) {
                var label = $('.card-limit-label').closest('.form-group').find('label');

                if (method === 'cardyfie') {
                    label.text('{{ __("Card Limit* (If Setup is 0, Card Creation is Unlimited)") }}');
                } else {
                    label.text('{{ __("Card limit admin") }}');
                }
            }

        })(jQuery);

    </script>
    <script>
        $('.copytext').on('click', function () {
                var copyText = $(this).closest('.input-group').find('.referralURL')[0];
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                document.execCommand("copy");

                throwMessage('success', ['{{ "Copy Successfully" }}']);
            });
    </script>
    <script>
        $(document).on('click', '.balance-toggle-btn', function () {
            var $btn    = $(this);
            var $card   = $btn.closest('.provider-account-balance');
            var $info   = $card.find('.user-info');
            var $badge  = $card.find('.user-badge');
            var $amount = $card.find('.balance-amount');
            var $icon   = $btn.find('i');

            if (!$info.hasClass('d-none')) {
                $info.addClass('d-none');
                $badge.addClass('d-none');
                $icon.removeClass('la-eye-slash').addClass('la-eye');
                return;
            }

            if ($card.data('loaded')) {
                $info.removeClass('d-none');
                $badge.removeClass('d-none');
                $icon.removeClass('la-eye').addClass('la-eye-slash');
                return;
            }

            $btn.prop('disabled', true);

            $.get($card.data('balance-url'), function (res) {
                var $badgeSpan = $('<span class="badge"></span>')
                    .addClass(res.status ? 'badge--success' : 'badge--danger')
                    .text(res.message);
                $badge.empty().append($badgeSpan);

                if (res.amount !== null) {
                    $amount.text('{{ get_default_currency_symbol() }} ' + res.amount);
                    $info.removeClass('d-none');
                } else {
                    $info.addClass('d-none');
                }

                $badge.removeClass('d-none');
                $icon.removeClass('la-eye').addClass('la-eye-slash');
                $card.data('loaded', true);
            }).fail(function () {
                throwMessage('error', ['{{ __("Failed To Fetch Balance.") }}']);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    </script>
@endpush
