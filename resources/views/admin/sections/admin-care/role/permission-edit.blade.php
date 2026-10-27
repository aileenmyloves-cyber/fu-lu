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
    <form class="table-wrapper" action="{{ setRoute('admin.admins.role.permission.update',$assign_permissions->slug) }}" method="POST">
        @csrf
        <div class="table-header">
            <div class="table-header-form">
                <div class="d-flex gap-3">
                    <div>
                        <h5 class="title">{{ __("Permission Name") }} :</h5>
                        <div class="form-group">
                            <input type="text" class="form--control" name="permission_name" value="{{ $assign_permissions->name }}" placeholder="{{ __("Enter the permission name") }}...">
                        </div>
                    </div>
                    <div>
                        <h5 class="title">{{ __("Select Role") }} </h5>
                        <div class="form-group">
                            <select class="select2-basic" name="role">
                                @foreach ($roles ?? [] as $item)
                                    <option value="{{ $item->id }}" @if($item->name == $assign_permissions->role->name) selected @endif>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-btn-area">
                <button type="submit" class="btn--base"><i class="fas fa-save me-1"></i> {{ __("Save Role Permission") }}</button>
            </div>
        </div>
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
                        <tr class="role-table-row">
                            <td>{{ $item['title'] ?? '' }}</td>
                            <td class="custom-data">
                                <table class="custom-table role">
                                    <tbody>
                                        @foreach ($item['sections'] ?? [] as $section_key => $sections)
                                            <tr>
                                                @php
                                                    $section_routes = collect($sections['routes'])->pluck('route')->all();

                                                    $has_all_permissions = collect($section_routes)->diff(
                                                        collect($assign_permissions->hasPermissions)->pluck('route')
                                                    )->isEmpty();
                                                @endphp
                                                <td>
                                                    <div class="custom-check-group dependency-check-group">
                                                        <input type="checkbox" id="currency-{{ $permission_key }}-{{ $section_key }}"  @if($has_all_permissions) checked @endif>
                                                        <label for="currency-{{ $permission_key }}-{{ $section_key }}">{{ $sections['title'] }}</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="custom-check-group-wrapper">
                                                        @foreach ($sections['routes'] ?? [] as $route_key => $routes)
                                                            @php
                                                                $isChecked = $assign_permissions->hasPermissions->contains('route', $routes['route']);
                                                            @endphp
                                                            <div class="custom-check-group">
                                                                <input type="checkbox" id="crIndex-{{ $permission_key }}-{{ $section_key }}-{{ $route_key }}" name="permissions[]" value="{{ $routes['route'] }}" @if($isChecked) checked @endif>
                                                                <label for="crIndex-{{ $permission_key }}-{{ $section_key }}-{{ $route_key }}">{{ $routes['title'] }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</div>
@endsection
