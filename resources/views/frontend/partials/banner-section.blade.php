@php
    $lang = selectedLang();
    $system_default = $not_removable_code;
    $banner_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::BANNER_SECTION);
    $banner = App\Models\Admin\SiteSections::getData($banner_slug)->first();
@endphp
<section class="banner-section bg_img" data-background="{{ get_image('frontend/') }}/images/banner/bg-1.jpg">
    <div class="container home-container">
        <div class="row mb-30-none">
            <div class="col-lg-6 col-md-6 mb-30">
                <div class="banner-thumb-area text-center">
                    <img src="{{ get_image(@$banner->value->images->banner_image, 'site-section') }}" alt="banner">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 mb-30">
                <div class="banner-content">
                    <span class="banner-sub-titel"><i
                            class="fas fa-qrcode"></i>{{ __($banner->value->language->$lang->title ?? $banner->value->language->$system_default->title) }}</span>
                    <h1 class="banner-title">
                        {{ __($banner->value->language->$lang->heading ?? $banner->value->language->$system_default->heading) }}
                    </h1>
                    <p>{{ __($banner->value->language->$lang->sub_heading ?? $banner->value->language->$system_default->sub_heading) }}
                    </p>
                    <div class="app-btn-area">
                        <a href="{{ @$app_urls->android_url }}" class="app-btn" target="_blank">
                            <div class="icon">
                                <img src="{{ get_image('frontend/') }}/images/app/play-store.png" alt="play-store">
                            </div>
                            <div class="content">
                                <span class="sub-title">{{ __('Get It On') }}</span>
                                <h5 class="title">{{ __('google Play') }}</h5>
                            </div>
                        </a>
                        <a href="{{ @$app_urls->iso_url }}" class="app-btn" target="_blank">
                            <div class="icon">
                                <img src="{{ get_image('frontend/') }}/images/app/apple-store.png" alt="play-store">
                            </div>
                            <div class="content">
                                <span class="sub-title">{{ __('Download On The') }}</span>
                                <h5 class="title">{{ __('Apple Store') }}</h5>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
