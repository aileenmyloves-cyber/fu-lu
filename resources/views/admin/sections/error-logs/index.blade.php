@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 194px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 150px !important;
        }
        pre{
            white-space: break-spaces;
            text-align: left;
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
    ], 'active' => __($page_title)])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                    <h5 class="title">{{ __($page_title) }}</h5>
                    <div class="table-btn-area">
                        <a href="{{ setRoute('admin.error.logs.clear') }}" class="btn btn--danger">{{ __("Clear All") }}</a>
                    </div>
                </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __("Log Entry") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $index => $log)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><pre>{{ $log }}</pre></td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 2])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
