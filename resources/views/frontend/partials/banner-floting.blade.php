
@php
    $lang = selectedLang();
    $system_default    = $not_removable_code;
    $banner_floting_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::BANNER_FLOTING);
    $banner_floting = App\Models\Admin\SiteSections::getData( $banner_floting_slug)->first();
@endphp

<section class="banner-floting-section {{  Route::currentRouteName() != 'index' ? 'ptb-120 mt-0' : ''}} ">
    <div class="container">
        <div class="row">
            <div class="col-xl-12">
                <div class="banner-floting-right-area">
                    <ul class="banner-floting-right-list">
                        @if(isset($banner_floting->value->items))
                            @foreach($banner_floting->value->items ?? [] as $key => $item)
                            <li><i class="fas fa-check"></i>{{ $item->language->$lang->name ?? $item->language->$system_default->name }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <div class="banner-floting-right-content">
                        <h3 class="title">{{ __($banner_floting->value->language->$lang->title ?? $banner_floting->value->language->$system_default->title) }}</h3>
                        <p>{{ __($banner_floting->value->language->$lang->sub_title ?? $banner_floting->value->language->$system_default->sub_title) }}</p>
                        <a href="{{url('/').'/'.@$banner_floting->value->language->$lang->button_link}}" class="link-area">{{ __($banner_floting->value->language->$lang->button_name ?? $banner_floting->value->language->$system_default->button_name) }} <i class="fas fa-long-arrow-alt-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
