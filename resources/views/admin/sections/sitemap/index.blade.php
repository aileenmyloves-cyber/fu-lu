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
    ], 'active' => __("Sitemap")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <div class="row mb-10-none">
                <div class="col-xl-12 form-group">
                    <label>{{ __("Status") }}</label>
                    <div class="input-group append">
                        <span class="input-group-text">#</span>
                        <input type="text" class="form--control" value="{{ $sitemap_exists ? __('Generated') : __('Not generated yet') }}" readonly>
                    </div>
                </div>

                @if ($sitemap_exists)
                    <div class="col-xl-12 form-group">
                        <label>{{ __("Last Generated At") }}</label>
                        <div class="input-group append">
                            <span class="input-group-text">#</span>
                            <input type="text" class="form--control" value="{{ $last_generated_at->format('d M, Y h:i A') }}" readonly>
                        </div>
                    </div>

                    <div class="col-xl-12 form-group">
                        <label>{{ __("Sitemap URL") }}</label>
                        <div class="input-group append">
                            <span class="input-group-text">#</span>
                            <input type="text" class="form--control" value="{{ $sitemap_url }}" readonly>
                        </div>
                        <a href="{{ $sitemap_url }}" target="_blank" class="d-inline-block mt-2">{{ __("View Sitemap") }}</a>
                    </div>
                @endif

                <div class="col-xl-12 form-group">
                    <form class="card-form" action="{{ setRoute('admin.sitemap.generate') }}" method="POST">
                        @csrf
                        @include('admin.components.button.form-btn',[
                            'class'         => "btn-loading",
                            'text'          => $sitemap_exists ? __("Regenerate Sitemap") : __("Generate Sitemap"),
                            'permission'    => "admin.sitemap.generate"
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
