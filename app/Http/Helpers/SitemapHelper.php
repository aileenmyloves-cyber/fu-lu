<?php

namespace App\Http\Helpers;

use App\Constants\GlobalConst;
use App\Models\Admin\SetupPage;
use App\Models\Blog;
use Exception;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapHelper
{
    /**
     * Generate the public sitemap.xml file from the current route/content
     * state. Shared by the admin "Generate Sitemap" button and the
     * installer (which runs this once the real base URL is known).
     *
     * @return bool
     */
    public static function generate(): bool
    {
        try {
            $sitemap = Sitemap::create();

            $sitemap->add(Url::create(route('index'))->setPriority(1.0));
            $sitemap->add(Url::create(route('about'))->setPriority(0.8));
            $sitemap->add(Url::create(route('service'))->setPriority(0.8));
            $sitemap->add(Url::create(route('pricing'))->setPriority(0.7));
            $sitemap->add(Url::create(route('faq'))->setPriority(0.8));
            $sitemap->add(Url::create(route('blog'))->setPriority(0.8));
            $sitemap->add(Url::create(route('agent'))->setPriority(0.8));
            $sitemap->add(Url::create(route('merchant'))->setPriority(0.8));
            $sitemap->add(Url::create(route('contact'))->setPriority(0.6));

            Blog::where('status', GlobalConst::ACTIVE)
                ->select('id','slug', 'created_at', 'updated_at')
                ->get()
                ->each(function ($announcement) use ($sitemap) {
                    $sitemap->add(
                        Url::create(route('blog.details', [$announcement->id,$announcement->slug]))
                            ->setLastModificationDate($announcement->updated_at ?? $announcement->created_at)
                            ->setPriority(0.6)
                    );
                });

            SetupPage::where('status', GlobalConst::ACTIVE)
                ->where('type', 'useful-links')
                ->select('slug', 'created_at', 'updated_at')
                ->get()
                ->each(function ($link) use ($sitemap) {
                    $sitemap->add(
                        Url::create(route('useful.link', $link->slug))
                            ->setLastModificationDate($link->updated_at ?? $link->created_at)
                            ->setPriority(0.4)
                    );
                });

            $sitemap->writeToFile(public_path('sitemap.xml'));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Point robots.txt's "Sitemap:" directive at the given base URL.
     * Replaces an existing directive if present, otherwise appends one.
     *
     * @return bool
     */
    public static function updateRobotsSitemapUrl(string $base_url): bool
    {
        $robots_path = public_path('robots.txt');

        if (! file_exists($robots_path)) {
            return false;
        }

        $sitemap_url = rtrim($base_url, '/').'/sitemap.xml';

        try {
            $content = File::get($robots_path);

            if (preg_match('/^Sitemap:.*$/mi', $content)) {
                $content = preg_replace('/^Sitemap:.*$/mi', 'Sitemap: '.$sitemap_url, $content);
            } else {
                $content = rtrim($content)."\n\nSitemap: ".$sitemap_url."\n";
            }

            File::put($robots_path, $content);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
