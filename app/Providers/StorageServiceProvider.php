<?php

namespace App\Providers;

use App\Models\Admin\BasicSettings;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            $basic_settings = BasicSettings::first();

            if ($basic_settings) {
                $storage_config = $basic_settings->storage_config;

                if ($storage_config?->method == 'public') {

                    // set local configuration
                    Config::set('filesystems.default', $storage_config?->method);

                } elseif ($storage_config?->method == 's3') {

                    Config::set('filesystems.disks.s3', [
                        'driver' => 's3',
                        'key' => $storage_config?->access_key_id,
                        'secret' => $storage_config?->secret_access_key,
                        'region' => $storage_config?->default_region,
                        'bucket' => $storage_config?->bucket,
                        'endpoint' => $storage_config?->endpoint,
                        'url' => $storage_config?->url,
                    ]);

                    Config::set('filesystems.default', $storage_config?->method);
                }
            }
        } catch (Exception $e) {
            // catch
        }
    }
}
