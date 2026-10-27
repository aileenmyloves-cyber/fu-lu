<div class="custom-card mb-10">
    <div class="card-header">
        <h6 class="title">{{ __($title) ?? "" }}</h6>
    </div>
    <div class="card-body">
        <form class="card-form" method="POST" action="{{ $route ?? "" }}">
            @csrf
            @method("PUT")
            @php
                if($item->slug == 'pay-link' || $item->slug == 'gift_card'){
                    $col = 'col-xl-6 col-lg-6';
                }else{
                    $col = $item->agent_profit == true ? 'col-xl-6 col-lg-6': 'col-xl-4 col-lg-4 ';
                }
            @endphp

            <input type="hidden" value="{{ $item->slug }}" name="slug">

            @if ($item->slug != 'trade')
                <div class="row">
                    <div class="{{$col}} mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Charges") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Fixed Charge") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_fixed_charge',get_amount($data->fixed_charge,null,get_wallet_precision())) }}" name="{{$data->slug}}_fixed_charge">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Percent Charge") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_percent_charge',get_amount($data->percent_charge,null,get_wallet_precision())) }}" name="{{$data->slug}}_percent_charge">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="{{$col}} mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Range") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-6 col-lg-6  form-group">
                                        <label>{{ __("Minimum amount") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_min_limit',get_amount($data->min_limit,null,get_wallet_precision())) }}" name="{{$data->slug}}_min_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Maximum amount") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_max_limit',get_amount($data->max_limit,null,get_wallet_precision())) }}" name="{{$data->slug}}_max_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($item->slug != 'pay-link' && $item->slug != 'gift_card')
                    <div class="{{ $item->agent_profit == true ? 'col-xl-6 col-lg-6': 'col-xl-4 col-lg-4 '}} mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Limit") }} <span class="small text--base">({{ __("Execute if the value is greater than zero") }})</span></h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Daily Limit") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_daily_limit',get_amount($data->daily_limit,null,get_wallet_precision())) }}" name="{{$data->slug}}_daily_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Monthly Limit") }}* </label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_monthly_limit',get_amount($data->monthly_limit,null,get_wallet_precision())) }}" name="{{$data->slug}}_monthly_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if( $item->agent_profit == true)
                    <div class="col-xl-6 col-lg-6 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title-agent">{{ __("Agent Profits") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Fixed Commissions") }}* </label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_agent_fixed_commissions',get_amount($data->agent_fixed_commissions,null,get_wallet_precision())) }}" name="{{$data->slug}}_agent_fixed_commissions">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Percent Commissions") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($data->slug.'_agent_percent_commissions',get_amount($data->agent_percent_commissions,null,get_wallet_precision())) }}" name="{{$data->slug}}_agent_percent_commissions">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            @endif

            @if ($item->slug == 'trade')
                <div class="row">
                    <div class="col-xl-12 col-lg-12 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __($item->title) }}</h5>
                                <button type="button" class="btn btn--base {{ $item->slug.'addUserData' }}">
                                    <i class="la la-fw la-plus"></i>@lang('add New')
                                </button>
                            </div>

                            <div class="card-inner-body {{ $item->slug.'addedField' }}">
                                @foreach (json_decode($item->intervals, true) ?? [] as $data)
                                <div class="row align-items-end {{ $item->slug.'user-data' }}">
                                    <div class="col-11">
                                        <div class="row">
                                            <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                                <label>{{ __("Minimum Amount") }}</label>
                                                <div class="input-group">
                                                    <input type="number" class="form--control" value="{{ $data['min_limit'] }}" name="{{$item->slug}}_min_limit[]" placeholder="0.00">
                                                    <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                                <label>{{ __("Maximum Amount") }}</label>
                                                <div class="input-group">
                                                    <input type="number" class="form--control" value="{{ $data['max_limit'] }}" name="{{$item->slug}}_max_limit[]" placeholder="0.00">
                                                    <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                                <label>{{ __("Charge Fixed") }}</label>
                                                <div class="input-group">
                                                    <input type="number" class="form--control" value="{{ $data['charge']  }}" name="{{$item->slug}}_charge[]" placeholder="0.00">
                                                    <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                                <label>{{ __("Charge Percent") }}</label>
                                                <div class="input-group">
                                                    <input type="number" class="form--control" value="{{ $data['percent'] }}" name="{{$item->slug}}_percent[]" placeholder="0.00">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-1 col-xl-1 col-lg-1 form-group">
                                        <div class="input-group">
                                            <button class="btn btn--danger btn-lg {{ $item->slug.'removeBtn' }} w-100" type="button">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                        </div>
                    </div>

                    <div class="col-xl-12 col-lg-12 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Limit") }} <span class="small text--base">({{ __("Execute if the value is greater than zero") }})</span></h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Daily Limit") }}*</label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old(@$item->slug.'_daily_limit',get_amount($item->daily_limit,null,get_wallet_precision())) }}" name="{{$item->slug}}_daily_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Monthly Limit") }}* </label>
                                        <div class="input-group">
                                            <input type="text" class="form--control number-input" value="{{ old($item->slug.'_monthly_limit',get_amount($item->monthly_limit,null,get_wallet_precision())) }}" name="{{$item->slug}}_monthly_limit">
                                            <span class="input-group-text">{{ get_default_currency_code($default_currency) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            @endif


            <div class="row mb-10-none">
                <div class="col-xl-12 col-lg-12 form-group">
                    @include('admin.components.button.form-btn',[
                        'text'          => "update",
                        'class'         => "w-100 btn-loading",
                        'permission'    => "admin.trx.settings.charges.update",
                    ])
                </div>
            </div>
        </form>
    </div>
</div>

@push('script')
<script>

    (function ($) {
        "use strict";

        $('.{{ $item->slug.'addUserData' }}').on('click', function () {
            var html = `
            <div class="row align-items-end {{ $item->slug.'user-data' }}">
                            <div class="col-11">
                                <div class="row">
                                    <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                        <label>{{ __("Minimum Amount") }}</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control"  name="{{$item->slug}}_min_limit[]" placeholder="0.00">
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                        <label>{{ __("Maximum Amount") }}</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control"  name="{{$item->slug}}_max_limit[]" placeholder="0.00">
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                        <label>{{ __("Fixed Charge") }}</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control" name="{{$item->slug}}_charge[]" placeholder="0.00">
                                            <span class="input-group-text">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-xl-3 col-lg-3 form-group">
                                        <label>{{ __("Percent Charge") }}</label>
                                        <div class="input-group">
                                            <input type="number" class="form--control"  name="{{$item->slug}}_percent[]" placeholder="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-1 col-xl-1 col-lg-1 form-group">
                                <div class="input-group">
                                    <button class="btn btn--danger btn-lg {{ $item->slug.'removeBtn' }} w-100" type="button">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>`;

            $('.{{ $item->slug.'addedField' }}').append(html);
        });

        $(document).on('click', '.{{ $item->slug."removeBtn" }}', function () {
            $(this).closest(".{{ $item->slug.'user-data' }}").remove();
        });

    })(jQuery);

</script>
@endpush
