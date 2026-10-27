<?php

namespace App\Constants;

class SiteSectionConst{
    const AUTH_SECTION          = "Auth Section";
    const APP_SECTION           = "App Section";
    const BANNER_SECTION        = "Banner Section";
    const BANNER_FLOTING        = "Banner Floting";
    const ABOUT_SECTION         = "About Section";
    const PRICING_SECTION       = "Pricing Section";
    const WORK_SECTION          = "Work Section";
    const SECURITY_SECTION      = "Security Section";
    const OVERVIEW_SECTION      = "Overview Section";
    const CHOOSE_SECTION        = "Why Choose Us Section";
    const SERVICE_SECTION       = "Service Section";
    const FAQ_SECTION           = "FAQ Section";
    const TESTIMONIAL_SECTION   = "Testimonials Section";
    const BRAND_SECTION         = "Brand Section";
    const CONTACT_SECTION       = "Contact Section";
    const FOOTER_SECTION        = "Footer Section";
    const BLOG_SECTION          = "Blog Section";
    const AGENT_SECTION         = "Agent Section";
    const MERCHANT_SECTION      = "Merchant Section";
    const MERCHANT_APP_SECTION  = "Merchant App Section";
    const AGENT_APP_SECTION     = "Agent App Section";
    const DEVELOPER_INTRO       = "Developer Introduction Section";
    const DEVELOPER_FAQ_SECTION = "Developer FAQ Section";

    const NOT_DISPLAY_COOKIE_SECTION          = "site_cookie";
    const NOT_DISPLAY_APP_SECTION             = "app-section";
    const NOT_DISPLAY_AUTH_SECTION            = "auth-section";
    const NOT_DISPLAY_FOOTER_SECTION          = "footer-section";
    const NOT_DISPLAY_BLOG_SECTION            = "blog-section";
    const NOT_DISPLAY_MERCHANT_SECTION        = "merchant-section";
    const NOT_DISPLAY_MERCHANT_APP_SECTION    = "merchant-app-section";
    const NOT_DISPLAY_AGENT_SECTION           = "agent-section";
    const NOT_DISPLAY_AGENT_APP_SECTION       = "agent-app-section";
    const NOT_DISPLAY_PRICING_SECTION         = "pricing-section";
    const NOT_DISPLAY_DEVELOPER_INTRO_SECTION = "developer-introduction-section";
    const NOT_DISPLAY_DEVELOPER_FAQ_SECTION   = "developer-faq-section";

    public static function notDisplaySections(): array{
        return [
            self::NOT_DISPLAY_COOKIE_SECTION,
            self::NOT_DISPLAY_APP_SECTION,
            self::NOT_DISPLAY_AUTH_SECTION,
            self::NOT_DISPLAY_FOOTER_SECTION,
            self::NOT_DISPLAY_BLOG_SECTION,
            self::NOT_DISPLAY_MERCHANT_SECTION,
            self::NOT_DISPLAY_MERCHANT_APP_SECTION,
            self::NOT_DISPLAY_AGENT_SECTION,
            self::NOT_DISPLAY_AGENT_APP_SECTION,
            self::NOT_DISPLAY_PRICING_SECTION,
            self::NOT_DISPLAY_DEVELOPER_INTRO_SECTION,
            self::NOT_DISPLAY_DEVELOPER_FAQ_SECTION
        ];
    }
}