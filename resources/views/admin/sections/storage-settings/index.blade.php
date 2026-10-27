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
    ], 'active' => __("Storage Settings")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("Storage Settings") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" method="POST" action="{{ setRoute('admin.storage.settings.update') }}">
                @csrf
                @method("PUT")
                <div class="row mb-10-none">

                    <div class="col-xl-12 col-lg-12 ">
                        <div class="row align-items-end">
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __("Storage Method") }}*</label>
                                <select class="form--control nice-select" name="method">
                                    <option disabled selected>{{ __("Select Method") }}</option>
                                    <option value="public" @if (old('method', $storage_config?->method) == "public")
                                        @selected(true)
                                    @endif>{{ __("Local Storage") }}</option>
                                    <option value="s3" @if (old('method', $storage_config?->method) == "s3")
                                        @selected(true)
                                    @endif>{{ __("S3 Storage") }}</option>
                                </select>
                                @error("method")
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- credentials input fields START --}}
                    <div class="input-fields local-fields" @if ($storage_config?->method != "public") style="display: none" @endif>
                        
                    </div>

                    <div class="input-fields s3-fields" @if ($storage_config?->method != "s3") style="display: none" @endif>
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Access Key ID"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'access_key_id',
                                    'value'         => old('access_key_id', Str::limit($storage_config->access_key_id ?? "", 8, "...")),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Secret Access Key"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'secret_access_key',
                                    'type'          => 'text',
                                    'value'         => old('secret_access_key', Str::limit($storage_config->secret_access_key ?? "", 8, '...')),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Default Region"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'default_region',
                                    'value'         => old('default_region', $storage_config->default_region ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Storage Bucket"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'bucket',
                                    'value'         => old('bucket', $storage_config->bucket ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Endpoint (AWS, Digital Ocean etc.)"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'endpoint',
                                    'value'         => old('endpoint', $storage_config->endpoint ?? ""),
                                ])
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("URL (AWS, Digital Ocean etc.)"),
                                    'label_after'   => "*",
                                    'placeholder'   => __("Write Here").'...',
                                    'name'          => 'url',
                                    'value'         => old('url', $storage_config->url ?? ""),
                                ])
                            </div>
                        </div>
                    </div>
                    {{-- credentials input fields END --}}

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Update"),
                            'permission'    => "admin.storage.settings.update",
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

        $(document).ready(function() {
            let selectedValue = $("select[name=method]").val();

            if(selectedValue) {
                $("."+selectedValue+"-fields").show();
            }
        });
    </script>

@endpush