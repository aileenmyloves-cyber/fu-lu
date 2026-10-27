<div class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-menu-inner-wrapper">
            <div class="sidebar-logo">
                <a href="{{ setRoute('index') }}" class="sidebar-main-logo">
                    <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings, 'dark') }}"
                        data-dark_img="{{ get_logo($basic_settings) }}" alt="logo">
                </a>
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="sidebar-menu-wrapper">
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.dashboard') }}" class="{{ makeActive('user.dashboard') }}">
                            <i class="menu-icon fas fa-rocket"></i>
                            <span class="menu-title">{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-th-large"></i>
                            <span class="menu-title">{{ __('Overview') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                @if (module_access('receive-money', $module)->status)
                                    <a href="{{ setRoute('user.receive.money.index') }}"
                                        class="submenu-style {{ makeActive('user.receive.money.index') }}">
                                        <i class="submenu-icon fas fa-receipt text--base"></i>
                                        <span class="menu-title">{{ __('Receive Money') }}</span>
                                    </a>
                                @endif
                                @if (isset($referral_info) && $referral_info->status == true)
                                    <a href="{{ setRoute('user.refer.level.index') }}"
                                        class="submenu-style {{ makeActive('user.refer.level.index') }}">
                                        <i class="submenu-icon fas fa-level-up-alt text--base"></i>
                                        <span class="menu-title">{{ __('Referral Status') }}</span>
                                    </a>
                                @endif
                                @if (module_access('remittance-money', $module)->status)
                                    <a href="{{ setRoute('user.receipient.index') }}"
                                        class="submenu-style {{ makeActive('user.receipient.index') }}">
                                        <i class="submenu-icon fas fa-user-check text--base"></i>
                                        <span class="menu-title">{{ __('Saved Recipients') }}</span>
                                    </a>
                                @endif
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fa fa-wallet"></i>
                            <span class="menu-title">{{ __('wallet') }}</span>
                        </a>

                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">

                                @if (module_access('add-money', $module)->status)
                                    <a href="{{ setRoute('user.add.money.index') }}"
                                        class="submenu-style {{ makeActive('user.add.money.index') }}">
                                        <i class="submenu-icon fas fa-plus-circle text--base"></i>
                                        <span class="menu-title">{{ __('Add Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('withdraw-money', $module)->status)
                                    <a href="{{ setRoute('user.money.out.index') }}"
                                        class="submenu-style {{ makeActive('user.money.out.index') }}">
                                        <i class="submenu-icon fas fa-arrow-alt-circle-right text--base"></i>
                                        <span class="menu-title">{{ __('withdraw') }}</span>
                                    </a>
                                @endif
                                @if (module_access('money-exchange', $module)->status)
                                    <a href="{{ setRoute('user.money.exchange.index') }}"
                                        class="submenu-style {{ makeActive('user.money.exchange.index') }}">
                                        <i class="submenu-icon fas fa-exchange-alt text--base"></i>
                                        <span class="menu-title">{{ __('Money Exchange') }}</span>
                                    </a>
                                @endif


                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-paper-plane"></i>
                            <span class="menu-title">{{ __('Transfer') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">

                                @if (module_access('send-money', $module)->status)
                                    <a href="{{ setRoute('user.send.money.index') }}"
                                        class="submenu-style {{ makeActive('user.send.money.index') }}">
                                        <i class="submenu-icon fas fa-paper-plane text--base"></i>
                                        <span class="menu-title">{{ __('Send Money') }}</span>
                                    </a>
                                @endif

                                @if (module_access('make-payment', $module)->status)
                                    <a href="{{ setRoute('user.make.payment.index') }}"
                                        class="submenu-style {{ makeActive('user.make.payment.index') }}">
                                        <i class="submenu-icon fas fa-arrow-alt-circle-left text--base"></i>
                                        <span class="menu-title">{{ __('Make Payment') }}</span>
                                    </a>
                                @endif
                                @if (module_access('money-out', $module)->status)
                                    <a href="{{ setRoute('user.agent.money.out.index') }}"
                                        class="submenu-style {{ makeActive('user.agent.money.out.index') }}">
                                        <i class="submenu-icon fas fa-arrow-alt-circle-left text--base"></i>
                                        <span class="menu-title">{{ __('Money Out') }}</span>
                                    </a>
                                @endif
                                @if (module_access('request-money', $module)->status)
                                    <a href="{{ setRoute('user.request.money.index') }}"
                                        class="submenu-style {{ makeActive('user.request.money.index') }}">
                                        <i class="submenu-icon fas fa-hand-holding-usd text--base"></i>
                                        <span class="menu-title">{{ __('request Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('pay-link', $module)->status)
                                    <a href="{{ setRoute('user.payment-link.index') }}"
                                        class="submenu-style {{ makeActive('user.payment-link.index') }}">
                                        <i class="submenu-icon fas fa-link text--base"></i>
                                        <span class="menu-title">{{ __('Payment Link') }}</span>
                                    </a>
                                @endif


                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-poll-h"></i>
                            <span class="menu-title">{{ __('Services') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                @if (module_access('remittance-money', $module)->status)
                                    <a href="{{ setRoute('user.remittance.index') }}"
                                        class="submenu-style {{ makeActive('user.remittance.index') }}">
                                        <i class="submenu-icon fas fa-coins text--base"></i>
                                        <span class="menu-title">{{ __('Remittance') }}</span>
                                    </a>
                                @endif
                                @if (module_access('bill-pay', $module)->status)
                                    <a href="{{ setRoute('user.bill.pay.index') }}"
                                        class="submenu-style {{ makeActive('user.bill.pay.index') }}">
                                        <i class="submenu-icon fas fa-shopping-bag text--base"></i>
                                        <span class="menu-title">{{ __('Bill Pay') }}</span>
                                    </a>
                                @endif
                                @if (module_access('mobile-top-up', $module)->status)
                                    <a href="{{ setRoute('user.mobile.topup.index') }}"
                                        class="submenu-style {{ makeActive('user.mobile.topup.index') }}">
                                        <i class="submenu-icon fas fa-mobile text--base"></i>
                                        <span class="menu-title">{{ __('Mobile Topup') }}</span>
                                    </a>
                                @endif
                                @if (module_access('trade', $module)->status)
                                    <a href="{{ setRoute('user.trade.index') }}"
                                        class="submenu-style {{ makeActive('user.trade.index') }}">
                                        <i class="submenu-icon fas fa-stream text--base"></i>
                                        <span class="menu-title">{{ __('P2P Trade') }}</span>
                                    </a>
                                @endif

                                @if (module_access('my-chat', $module)->status)
                                    <a href="{{ setRoute('user.p2p.chat.index') }}"
                                        class="submenu-style {{ makeActive('user.p2p.chat.index') }}">
                                        <i class="submenu-icon fas fa-comment-dots text--base"></i>
                                        <span class="menu-title">{{ __('My Chat') }}</span>
                                    </a>
                                @endif

                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-credit-card"></i>
                            <span class="menu-title">{{ __('nav_cards') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                @if (module_access('virtual-card', $module)->status)

                                    @if (virtual_card_system('flutterwave'))
                                        <a href="{{ setRoute('user.virtual.card.index') }}"
                                            class="submenu-style {{ makeActive('user.virtual.card.index') }}">
                                            <i class="submenu-icon fas fa-credit-card text--base"></i>
                                            <span class="menu-title">{{ __('Virtual Card') }}</span>
                                        </a>
                                    @elseif(virtual_card_system('sudo'))
                                        <a href="{{ setRoute('user.sudo.virtual.card.index') }}"
                                            class="submenu-style {{ makeActive('user.sudo.virtual.card.index') }}">
                                            <i class="submenu-icon fas fa-credit-card text--base"></i>
                                            <span class="menu-title">{{ __('Virtual Card') }}</span>
                                        </a>
                                    @elseif(virtual_card_system('stripe'))
                                        <a href="{{ setRoute('user.stripe.virtual.card.index') }}"
                                            class="submenu-style {{ makeActive('user.stripe.virtual.card.index') }}">
                                            <i class="submenu-icon fas fa-credit-card text--base"></i>
                                            <span class="menu-title">{{ __('Virtual Card') }}</span>
                                        </a>
                                    @elseif(virtual_card_system('strowallet'))
                                        <a href="{{ setRoute('user.strowallet.virtual.card.index') }}"
                                            class="submenu-style {{ makeActive('user.strowallet.virtual.card.index') }}">
                                            <i class="submenu-icon fas fa-credit-card text--base"></i>
                                            <span class="menu-title">{{ __('Virtual Card') }}</span>
                                        </a>
                                    @elseif(virtual_card_system('cardyfie'))
                                        <a href="{{ setRoute('user.cardyfie.virtual.card.index') }}"
                                            class="submenu-style {{ makeActive('user.cardyfie.virtual.card.index') }}">
                                            <i class="submenu-icon fas fa-credit-card text--base"></i>
                                            <span class="menu-title">{{ __('Virtual Card') }}</span>
                                        </a>
                                    @endif
                                @endif

                                @if (module_access('gift-cards', $module)->status)
                                    <a href="{{ setRoute('user.gift.card.index') }}"
                                        class="submenu-style {{ makeActive('user.gift.card.index') }}">
                                        <i class="submenu-icon fas fa-gift text--base"></i>
                                        <span class="menu-title">{{ __('Gift Card') }}</span>
                                    </a>
                                @endif


                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-user-shield"></i>
                            <span class="menu-title">{{ __('Security') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.security.google.2fa') }}"
                                    class="submenu-style {{ makeActive('user.security.google.2fa') }}">
                                    <i class="submenu-icon fas fa-unlock text--base"></i>
                                    <span class="menu-title">{{ __('2FA Security') }}</span>
                                </a>
                                @if ($basic_settings->user_pin_verification == true)
                                    <a href="{{ setRoute('user.setup.pin.index') }}"
                                        class="submenu-style {{ makeActive('user.setup.pin.index') }}">
                                        <i class="submenu-icon fas fa-key text--base"></i>
                                        <span class="menu-title">{{ __('Setup PIN') }}</span>
                                    </a>
                                @endif
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-item sidebar-dropdown">

                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-arrows-alt-h"></i>
                            <span class="menu-title">{{ __('Transactions') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.transactions.index') }}"
                                    class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index') }}">
                                    <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                    <span class="menu-title">{{ __('All Transactions') }}</span>
                                </a>
                                @if (module_access('add-money', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'add-money') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'add-money') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Add Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('withdraw-money', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'withdraw') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'withdraw') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('withdraw') }}</span>
                                    </a>
                                @endif
                                @if (module_access('money-exchange', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'money-exchange') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'money-exchange') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Exchange Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('send-money', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'transfer-money') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'transfer-money') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Send Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('make-payment', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'make-payment') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'make-payment') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Make Payment') }}</span>
                                    </a>
                                @endif
                                @if (module_access('money-out', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'money-out') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'money-out') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Money Out') }}</span>
                                    </a>
                                @endif
                                @if (module_access('request-money', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'request-money') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'request-money') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('request Money') }}</span>
                                    </a>
                                @endif
                                @if (module_access('pay-link', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'payment-link') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'payment-link') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Payment Link') }}</span>
                                    </a>
                                @endif
                                @if (module_access('remittance-money', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'remittance') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'remittance') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Remittance') }}</span>
                                    </a>
                                @endif
                                @if (module_access('bill-pay', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'bill-pay') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'bill-pay') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Bill Pay') }}</span>
                                    </a>
                                @endif
                                @if (module_access('mobile-top-up', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'mobile-topup') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'mobile-topup') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Mobile Topup') }}</span>
                                    </a>
                                @endif
                                @if (module_access('trade', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'trade-log') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'trade-log') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Trade') }}</span>
                                    </a>
                                @endif
                                @if (module_access('trade', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'marketplace-log') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'marketplace-log') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Marketplace') }}</span>
                                    </a>
                                @endif
                                @if (module_access('virtual-card', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'virtual-card') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'virtual-card') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Virtual Card') }}</span>
                                    </a>
                                @endif
                                @if (module_access('gift-cards', $module)->status)
                                    <a href="{{ setRoute('user.transactions.index', 'gift-card') }}"
                                        class="submenu-style {{ makeActiveRouteSlugOnly('user.transactions.index', 'gift-card') }}">
                                        <i class="submenu-icon fas fa-ellipsis-h text--base"></i>
                                        <span class="menu-title">{{ __('Gift Card') }}</span>
                                    </a>
                                @endif

                            </li>
                        </ul>
                    </li>
                    <li class="sidebar-menu-item sidebar-dropdown">
                        <a href="javascript:void(0)">
                            <i class="menu-icon fas fa-user-circle"></i>
                            <span class="menu-title">{{ __('Account') }}</span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.profile.index') }}"
                                    class="submenu-style {{ makeActive('user.profile.index') }}">
                                    <i class="submenu-icon fas fa-user-md text--base"></i>
                                    <span class="menu-title">{{ __('profile') }}</span>
                                </a>
                                <a href="javascript:void(0)" class="submenu-style logout-btn">
                                    <i class="submenu-icon fas fa-sign-out-alt text--base"></i>
                                    <span class="menu-title">{{ __('Logout') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <div class="sidebar-doc-box bg_img" data-background="{{ get_image('frontend/') }}/images/element/support.jpg">
            <div class="sidebar-doc-icon">
                <i class="las la-question-circle"></i>
            </div>
            <div class="sidebar-doc-content">
                <h4 class="title">{{ __('help Center') }}?</h4>
                <p>{{ __('How can we help you?') }}</p>
                <div class="sidebar-doc-btn">
                    <a href="{{ setRoute('user.support.ticket.index') }}"
                        class="btn--base w-100">{{ __('Get Support') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('script')
    <script>
        $(".logout-btn").click(function() {
            var actionRoute = "{{ setRoute('user.logout') }}";
            var target = 1;
            var sureText = '{{ __('Are you sure to') }}';
            var message = `${sureText} <strong>{{ __('Logout') }}</strong>?`;
            var logout = `{{ __('Logout') }}`;
            openAlertModal(actionRoute, target, message, logout, "POST");
        });
    </script>
@endpush
