
<div class="dashboard-list-item-wrapper">
    <div class="dashboard-list-item sent">
        <div class="dashboard-list-left">
            <div class="dashboard-list-user-wrapper">
                <div class="dashboard-list-user-icon">
                    @if ($item->user_id == Auth::id())
                        <i class="las la-arrow-down"></i>
                    @else
                        <i class="las la-arrow-up"></i>
                    @endif
                </div>
                <div class="dashboard-list-user-content">
                    <h4 class="title">{{ $item->trx_id }}</h4>
                    <span class="sub-title text--base">
                    @if ($item->user_id == Auth::id())
                        {{ __('Buy') }}
                    @else
                        {{ __('Sale') }}
                    @endif
                    <span class="badge {{ $item->stringMarketplaceStatus->class }} ms-2">{{ __($item->stringMarketplaceStatus->value) }}</span></span>
                </div>
            </div>
        </div>
        <div class="dashboard-list-right">
            <h4 class="main-money text--base">{{ getDynamicAmount($item->forexcrow->amount).' '.$item->forexcrow->saleCurrency->code }}</h4>
            <h6 class="exchange-money">{{ dateFormat('d M y', $item->created_at) }}</h6>
        </div>
    </div>
    <div class="preview-list-wrapper">
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="las la-id-card"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Trx ID') }}</span>
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span>{{ $item->trx_id }}</span>
            </div>
        </div>
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="las la-user"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Seller') }}</span>
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span>{{ @$item->forexcrow->user->firstname. ' ' .@$item->forexcrow->user->lastname }}</span>
            </div>
        </div>
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="las la-flag"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('status') }}</span>
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span>{{ __($item->stringMarketplaceStatus->value) }}</span>
            </div>
        </div>
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="las la-clock"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Date') }}</span>
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span>{{ dateFormat('d M y', $item->created_at) }} / {{ dateFormat('h:i A', $item->created_at) }}</span>
            </div>
        </div>
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="las la-coins"></i>
                    </div>
                    <div class="preview-list-user-content">
                        @if ($item->user_id == Auth::id())
                            <span>{{ __('Pay Amount') }}</span>
                        @else
                            <span>{{ __('Received Amount') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span class="text--base last">{{ getDynamicAmount($item->payable).' '.$item->forexcrow->rateCurrency->code }}</span>
            </div>
        </div>
    </div>
</div>
