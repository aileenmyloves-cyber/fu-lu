<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\SitemapHelper;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * Display the sitemap status page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = 'Sitemap';
        $sitemap_path = public_path('sitemap.xml');
        $sitemap_exists = file_exists($sitemap_path);
        $sitemap_url = url('sitemap.xml');
        $last_generated_at = $sitemap_exists ? Carbon::createFromTimestamp(filemtime($sitemap_path)) : null;

        return view('admin.sections.sitemap.index', compact(
            'page_title',
            'sitemap_exists',
            'sitemap_url',
            'last_generated_at',
        ));
    }

    /**
     * Generate the public sitemap.xml file.
     *
     * @return \Illuminate\Http\Response
     */
    public function generate()
    {
        if (! SitemapHelper::generate()) {
            return back()->with(['error' => [__('Failed to generate sitemap. Please try again.')]]);
        }

        SitemapHelper::updateRobotsSitemapUrl(url('/'));

        return back()->with(['success' => [__('Sitemap generated successfully!')]]);
    }
}
