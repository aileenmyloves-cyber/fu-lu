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
    ], 'active' => __("Setup Email")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("Email Method") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" method="POST" action="{{ setRoute('admin.setup.email.config.update') }}">
                @csrf
                @method("PUT")
                <div class="row mb-10-none">

                    <div class="col-xl-12 col-lg-12 ">
                        <div class="row align-items-end">
                            <div class="col-xl-10 col-lg-10 form-group">
                                <label>{{ __("Email Send Method*") }}</label>
                                <select class="form--control nice-select" name="method">
                                    <option disabled selected>{{ __("Select Method") }}</option>
                                    <option value="smtp" @if (isset($email_config->method) && $email_config->method == "smtp")
                                        @selected(true)
                                    @endif>{{ __("SMTP") }}</option>
                                    <option value="mailgun" @if (isset($email_config->method) && $email_config->method == "mailgun")
                                        @selected(true)
                                    @endif>{{ __("Mailgun") }}</option>
                                    <option value="reachead" @if (isset($email_config->method) && $email_config->method == "reachead")
                                        @selected(true)
                                    @endif>{{ __("Reachead") }}</option>
                                </select>
                                @error("method")
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col-xl-2 col-lg-2 form-group">
                                <!-- Open Modal For Test Email Send -->
                                @include('admin.components.link.custom',[
                                    'class'         => "btn--base modal-btn w-100",
                                    'href'          => "#test-mail",
                                    'text'          => __("Send Mail"),
                                    'permission'    => "admin.setup.email.test.mail.send",
                                ])
                            </div>
                        </div>
                    </div>

                    {{-- credentials input fields START --}}
                    <div class="input-fields smtp-fields" @if (isset($email_config->method) && $email_config->method != "smtp") style="display: none" @endif>
                        <div class="row">
                            <div class="col-xl-5 col-lg-5 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Host"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'host',
                                    'value'     => old('host',$email_config->host ?? ""),
                                ])
                            </div>
                            <div class="col-xl-5 col-lg-5 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Port"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'port',
                                    'type'      => 'number',
                                    'value'     => old('port',$email_config->port ?? ""),
                                ])
                            </div>
                            <div class="col-xl-2 col-lg-2 form-group">
                                @include('admin.components.form.switcher',[
                                    'label'     => __("Encryption"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'encryption',
                                    'options'   => [__('SSL') => "ssl",__('TLS') => "tls"],
                                    'value'     => old('encryption',$email_config->encryption ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Username"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'username',
                                    'value'     => old('username',$email_config->username ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __('Mail From Address').'*',
                                    'placeholder'   => __('From Address') ,
                                    'name'          => 'from_address',
                                    'value'         => old('from_address',$email_config->from ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group" >
                                @include('admin.components.form.email-input-password',[
                                    'label'         => __('password'),
                                    'label_after'   => "*",
                                    'placeholder'   => __('password') ,
                                    'name'          => 'password',
                                    'value'         => old('password',$email_config->password ?? ""),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="input-fields mailgun-fields" @if (isset($email_config->method) && $email_config->method != "mailgun") style="display: none" @endif>
                        <div class="row">
                            <div class="col-xl-5 col-lg-5 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Host"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'mailgun_host',
                                    'value'     => old('mailgun_host',$email_config->host ?? ""),
                                ])
                            </div>
                            <div class="col-xl-5 col-lg-5 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Port"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'mailgun_port',
                                    'type'      => 'number',
                                    'value'     => old('mailgun_port',$email_config->port ?? ""),
                                ])
                            </div>
                            <div class="col-xl-2 col-lg-2 form-group">
                                @include('admin.components.form.switcher',[
                                    'label'     => __("Encryption"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'mailgun_encryption',
                                    'options'   => [__('SSL') => "ssl",__('TLS') => "tls"],
                                    'value'     => old('mailgun_encryption',$email_config->encryption ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'     => __("Username"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'      => 'mailgun_username',
                                    'value'     => old('mailgun_username',$email_config->username ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.email-input-password',[
                                    'label'         => __('password'),
                                    'label_after'   => "*",
                                    'placeholder'   => __('password') ,
                                    'name'          => 'mailgun_password',
                                    'value'         => old('mailgun_password',$email_config->password ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __('Mail From Address').'*',
                                    'placeholder'   => __('From Address') ,
                                    'name'          => 'mailgun_from_address',
                                    'value'         => old('mailgun_from_address',$email_config->from ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __('Mailgun Domain').'*',
                                    'placeholder'   => __('Mailgun Domain') ,
                                    'name'          => 'mailgun_domain',
                                    'value'         => old('mailgun_domain',$email_config->domain ?? ""),
                                ])
                            </div>
                            <div class="col-xl-4 col-lg-4 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __('Mailgun Secret').'*',
                                    'placeholder'   => __('Mailgun Secret') ,
                                    'name'          => 'mailgun_secret',
                                    'value'         => old('mailgun_secret',$email_config->secret ?? ""),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="input-fields reachead-fields" @if (isset($email_config->method) && $email_config->method != "reachead") style="display: none" @endif>
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __('Mail From Address').'*',
                                    'placeholder'   => __('From Address') ,
                                    'name'          => 'reachead_from_address',
                                    'value'         => old('reachead_from_address',$email_config->from ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.email-input-password',[
                                    'label'         => __('api Key'),
                                    'label_after'   => "*",
                                    'placeholder'   => __('api Key') ,
                                    'name'          => 'reachead_api_key',
                                    'value'         => old('reachead_api_key',$email_config->api_key ?? ""),
                                ])
                            </div>
                        </div>
                    </div>
                    {{-- credentials input fields END --}}

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Update"),
                            'permission'    => "admin.setup.email.config.update",
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Test mail send modal --}}
    @include('admin.components.modals.send-text-mail')

@endsection

@push('script')

    <script>
        $("select[name=method]").change(function(){
            $(".input-fields").hide();
            $("."+$(this).val()+"-fields").show();
        });
    </script>

@endpush
