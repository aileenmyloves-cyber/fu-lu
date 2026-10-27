@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 194px !important;
        }
        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 150px !important;
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
    ], 'active' => __("Admin Care")])
@endsection

@section('content')
<div class="table-area">
    <div class="table-responsive">
        <table class="custom-table role">
            <thead>
                <tr>
                    <th></th>
                    <th>
                        <table class="custom-table head-role">
                            <thead>
                                <tr>
                                    <th>{{ __("Section Name") }}</th>
                                    <th>{{ __("Permission Area") }}</th>
                                </tr>
                            </thead>
                        </table>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions ?? [] as $permission_key => $item)
                    @php
                        $filtered_sections = collect($item['sections'] ?? [])->filter(function ($section) use ($assign_permissions) {
                            return collect($section['routes'])->pluck('route')
                                ->intersect(collect($assign_permissions->hasPermissions)->pluck('route'))
                                ->isNotEmpty();
                        });
                    @endphp
                    @if ($filtered_sections->isNotEmpty())
                        <tr class="role-table-row">
                            <td>{{ $item['title'] ?? '' }}</td>
                            <td class="custom-data">
                                <table class="custom-table role">
                                    <tbody>
                                        @foreach ($filtered_sections as $section_key => $sections)
                                            <tr>
                                                @php
                                                    $section_routes = collect($sections['routes'])->pluck('route')->all();
                                                    $has_all_permissions = collect($section_routes)->diff(
                                                        collect($assign_permissions->hasPermissions)->pluck('route')
                                                    )->isEmpty();
                                                @endphp
                                                <td>
                                                    <div class="ps-2">
                                                        <label>{{ $sections['title'] }}</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="custom-check-group-wrapper">
                                                        @foreach ($sections['routes'] ?? [] as $route_key => $routes)
                                                            @php
                                                                $isChecked = $assign_permissions->hasPermissions->contains('route', $routes['route']);
                                                            @endphp
                                                            @if ($isChecked)
                                                                <div class="ps-2">
                                                                    <label class="badge badge--base">{{ $routes['title'] }}</label>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
