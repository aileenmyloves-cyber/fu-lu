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
    ], 'active' => __("Admin Profile")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("2FA Settings") }}</h6>
        </div>

        <div class="card-body">

            <div class="text-center">
                <div class="img-box qr-code m-auto border">
                    {!! $qr_code !!}
                </div>

                <div class="secret-key mt-3">
                    <span class="fw-bold"  id="referralURL">{{ auth()->user()->two_factor_secret }}</span>
                    <div class="copy-text copy-btn copytext" data-copy-text="{{ auth()->user()->two_factor_secret }}"><i class="las la-copy"></i></div>
                </div>
            </div>

            @if (auth()->user()->two_factor_status)
                <button type="button" class="mt-3 btn--base bg-warning w-100 active-deactive-btn" data-confirm-btn-text="{{ __("Disable") }}">{{ __("Disable Two Factor Authenticator") }}</button>
                <br>
                <div class="text--danger mt-3 text-center fs-6">{{ __("Don't forget to add this application in your google authentication app. Otherwise you can't login in your account.") }}</div>
            @else
                <button type="button" class="mt-3 btn--base w-100 active-deactive-btn" data-confirm-btn-text="{{ __("Enable") }}">{{ __("Enable Two Factor Authenticator") }}</button>
            @endif

        </div>
    </div>
@endsection

@push('script')
    <script>
         $(".active-deactive-btn").click(function(){
            var actionRoute =  "{{ setRoute('admin.profile.google.2fa.status.update') }}";
            var target      = "{{ auth()->user()->id }}";
            var btnText     = $(this).text().trim();
            var csrf        = $("head meta[name=csrf-token]").attr("content");
            var promptText  = '{{ __("Enter the 6-digit code from your authenticator app to confirm.") }}';
            var codeLabel   = '{{ __("Authenticator code") }}';
            var content = `<div class="card twoFa-code-verify border-0">
                        <div class="card-body">
                            <form method="POST" action="${actionRoute}">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="target" value="${target}">
                                <div class="head mb-3"><strong>${btnText}</strong> {{ __('(Powered by google)') }}</div>
                                <div class="form-group mb-3">
                                    <label class="mb-2">${codeLabel}</label>
                                    <input type="text" name="code" class="form-control" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" required autofocus>
                                    <small class="text--muted d-block mt-2">${promptText}</small>
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn btn--info rounded text-light">{{ __('Close') }}</button>
                                    <button type="submit" class="btn btn--base btn-loading rounded text-light">${btnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`;
            openModalByContent({content: content});
        });

    </script>
@endpush
