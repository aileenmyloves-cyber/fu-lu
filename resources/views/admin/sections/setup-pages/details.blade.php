@extends('admin.layouts.master')

@push('css')
    <style>
        .switch-toggles {
            margin-left: auto;
        }

        .page-section-status {
            min-width: 200px;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __($page_title),
    ])
@endsection

@section('content')
    <div class="row justify-content-center mb-10-none">
        <div class="col-xl-12 col-lg-12 mb-10">
            <div class="custom-card">
                <div class="card-header">
                    <h5 class="title">{{ __($setup_page->title->title) }} {{ __('Page') }}</h5>
                    <a href="{{ setRoute('admin.setup.pages.index') }}" class="btn btn--base">{{ __('Back') }}</a>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute('admin.setup.pages.update.section', $setup_page->slug) }}"
                        method="POST">
                        @csrf
                        <ol id="page_sections" class="dragable-card-wrapper">

                            @foreach ($site_sections ?? [] as $item)
                                @php
                                    $page_section_data = $setup_page->sections
                                        ->where('site_section_id', $item->id)
                                        ->first();
                                    $status = $page_section_data ? $page_section_data->status : 0;
                                @endphp

                                <li class="dragable-item">
                                    <i class="dragable-icon"></i>
                                    <span
                                        class="dragable-text">{{ __(Str::title(str_replace(['_', '-'], ' ', $item->key))) }}</span>

                                    <div class="page-section-status">
                                        @include('admin.components.form.switcher', [
                                            'name' => 'status[]',
                                            'value' => $status,
                                            'options' => [__('Enable') => 1, __('Disable') => 0],
                                            'onload' => false,
                                            'permission' => 'admin.setup.pages.store.section',
                                        ])
                                    </div>
                                    <input name="sections[]" type="hidden" value="{{ $item->key }}">
                                </li>
                            @endforeach
                        </ol>
                        <button type="submit" class="btn--base w-100 mt-10">{{ __('Save & Change') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('backend/js/jquery-ui.js') }}"></script>


    <script>
        (function($) {
            "use strict";
            var initialSections = getSectionKeys();

            function getSectionKeys() {
                return $(document).find('#page_sections input[name="sections[]"]').map(function() {
                    return $(this).val();
                }).get();
            }

            $("#page_sections").sortable({
                items: "li:not(.empty-state)",
                update: () => handleShowSubmissionAlert()
            });

            $("#sections_items li").draggable({
                stop: function(event, ui) {
                    const element = ui.helper;
                    const key = element.data('key');
                    element.append(`<input type="hidden" name="sections[]" value="${key}">`)

                    if ($('#page_sections').children().length == 0) {
                        watchState(true);
                    }
                    handleShowSubmissionAlert();
                    $('#page_sections').removeClass('dropping');
                },
                start: function(event, ui, offset) {
                    const height = $('.empty-state').outerHeight();

                    if ($('#page_sections').children().length == 1) {
                        $('.empty-state').remove();
                    }

                    $('#page_sections').addClass('dropping').css('min-height', `${height}px`);
                },
                helper: function() {
                    var originalElement = $(this);
                    var originalWidth = '100%';
                    var clonedElement = originalElement.clone();
                    clonedElement.css('width', originalWidth);
                    const len = $('#page_sections').children().length;
                    return clonedElement;
                },
                connectToSortable: '#page_sections'
            });

            $("#page_sections").droppable({
                accept: '#sections_items li',
                drop: function(event, ui) {
                    let originalWidth = $(event.target).width();
                    $(this).append(ui.draggable);
                    ui.draggable.removeAttr('style');
                    ui.draggable.removeClass();
                    ui.draggable.addClass('highlight icon-move item ui-sortable-handle').css('height',
                        'auto');
                }
            });

            $(document).on('click', ".remove-icon", function() {
                $(this).parent('.highlight').remove();
                handleShowSubmissionAlert();
                watchState();
            });

            function watchState(override = false) {
                if ($('#page_sections').children().length == 0 || override) {
                    $('#page_sections').html(`<li class="empty-state">
                        <span>Drag & drop your section here</span>
                    </li>`);
                }
            }
        })(jQuery);
    </script>
@endpush
