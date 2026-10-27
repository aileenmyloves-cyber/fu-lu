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
    ], 'active' => __("File Permission")])
@endsection

@section('content')
    <div class="custom-card mb-24">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="title">{{ __("Current Permission Status") }}</h6>
            <div class="d-flex align-items-center" style="gap: 10px;">
                @if (app()->isLocal())
                    @include('admin.components.button.form-btn',[
                        'type'          => "button",
                        'class'         => "scan-permission-button",
                        'text'          => __("Scan Wrong Permission"),
                        'permission'    => "admin.file.permission.scan"
                    ])
                @endif
                @include('admin.components.button.form-btn',[
                    'type'          => "button",
                    'class'         => "btn-loading restore-permission-button",
                    'text'          => __("Restore Permission"),
                    'permission'    => "admin.file.permission.restore"
                ])
            </div>
        </div>
        <div class="card-body">
            <div class="table-area">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table two">
                            <thead>
                                <tr>
                                    <th>{{ __("Path") }}</th>
                                    <th>{{ __("type") }}</th>
                                    <th>{{ __("Permission") }}</th>
                                    <th>{{ __("Status") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $item)
                                    <tr>
                                        <td>{{ $item['label'] }}</td>
                                        <td>
                                            @if ($item['type'])
                                                <span>{{ ucfirst($item['type']) }}</span>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item['permission'])
                                                <span>{{ $item['permission'] }}</span>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item['status'] == 'ok')
                                                <span class="badge badge--success">{{ __($item['status_message']) }}</span>
                                            @else
                                                <span class="badge badge--warning">{{ __($item['status_message']) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('permission_failures') && count(session('permission_failures')) > 0)
        <div class="custom-card mt-2">
            <div class="card-header">
                <h6 class="title">{{ __("Items That Could Not Be Updated") }}</h6>
            </div>
            <div class="card-body">
                <div class="table-area">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table two">
                                <thead>
                                    <tr>
                                        <th>{{ __("File/Folder") }}</th>
                                        <th>{{ __("Reason") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (session('permission_failures') as $failure)
                                        <tr>
                                            <td>{{ $failure['path'] }}</td>
                                            <td>{{ $failure['reason'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    <script>
        $(document).on("click", ".restore-permission-button", function () {
            var actionRoute = "{{ setRoute('admin.file.permission.restore') }}";
            var message     = `{{ __('Are you sure you want to restore file and folder permissions for the whole project?') }}`;

            openDeleteModal(actionRoute, "restore", message, "{{ __('Restore') }}", "POST");
        });

        $(document).on("click", ".scan-permission-button", function () {
            var button       = $(this);
            var originalText = button.text();

            button.prop("disabled", true).text("{{ __('Scanning...') }}");

            $.get("{{ setRoute('admin.file.permission.scan') }}")
                .done(function (response) {
                    var data    = response.data;
                    var content = "";

                    if (data.total === 0) {
                        content = `<div class="card modal-alert border-0">
                                        <div class="card-body">
                                            <div class="head mb-3">{{ __('No permission issues found. Everything looks good!') }}</div>
                                            <div class="foot d-flex align-items-center justify-content-end">
                                                <button type="button" class="modal-close btn btn--info">{{ __('Close') }}</button>
                                            </div>
                                        </div>
                                    </div>`;
                    } else {
                        var rows = "";
                        $.each(data.items, function (index, item) {
                            rows += `<tr>
                                        <td>${item.path}</td>
                                        <td>${item.type}</td>
                                        <td>${item.current}</td>
                                        <td>${item.expected}</td>
                                    </tr>`;
                        });

                        var truncatedNote = "";
                        if (data.truncated) {
                            truncatedNote = `<div class="alert alert-warning mt-3 mb-0">{{ __('Showing first') }} ${data.items.length} {{ __('of') }} ${data.total} {{ __('items.') }} ${data.hidden_count} {{ __('more items were not shown.') }}</div>`;
                        }

                        content = `<div class="card modal-alert border-0">
                                        <div class="card-body">
                                            <div class="head mb-3">{{ __('Found') }} ${data.total} {{ __('items with incorrect permission') }}</div>
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="custom-table two">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __("Path") }}</th>
                                                            <th>{{ __("type") }}</th>
                                                            <th>{{ __("Current") }}</th>
                                                            <th>{{ __("Expected") }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>${rows}</tbody>
                                                </table>
                                            </div>
                                            ${truncatedNote}
                                            <div class="foot d-flex align-items-center justify-content-end mt-3">
                                                <button type="button" class="modal-close btn btn--info">{{ __('Close') }}</button>
                                            </div>
                                        </div>
                                    </div>`;
                    }

                    openModalByContent({content: content});
                })
                .fail(function () {
                    throwMessage("error", ["{{ __('Failed to scan permissions. Please try again.') }}"]);
                })
                .always(function () {
                    button.prop("disabled", false).text(originalText);
                });
        });
    </script>
@endpush
