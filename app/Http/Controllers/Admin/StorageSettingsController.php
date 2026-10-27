<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use Exception;
use Illuminate\Http\Request;

class StorageSettingsController extends Controller
{
    /**
     * show storage settings index page
     */
    public function index(Request $request)
    {
        $page_title = __('Storage Settings');

        $storage_config = BasicSettings::first()->storage_config;

        return view('admin.sections.storage-settings.index', compact(
            'page_title',
            'storage_config',
        ));
    }

    /**
     * update storage settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'method' => 'required|string|in:public,s3',
            'access_key_id' => 'required_if:method,s3|nullable|string',
            'secret_access_key' => 'required_if:method,s3|nullable|string',
            'default_region' => 'required_if:method,s3|nullable|string',
            'bucket' => 'required_if:method,s3|nullable|string',
            'endpoint' => 'required_if:method,s3|nullable|url',
            'url' => 'required_if:method,s3|nullable|url',
        ]);

        $basic_settings = BasicSettings::firstOrFail();

        try {
            if ($validated['method'] == 'public') {
                $basic_settings->update([
                    'storage_config' => [
                        'method' => $validated['method'],
                    ],
                ]);
            } else {
                $basic_settings->update([
                    'storage_config' => [
                        'method' => $validated['method'],
                        'access_key_id' => $validated['access_key_id'],
                        'secret_access_key' => $validated['secret_access_key'],
                        'default_region' => $validated['default_region'],
                        'bucket' => $validated['bucket'],
                        'endpoint' => $validated['endpoint'],
                        'url' => $validated['url'],
                    ],
                ]);
            }
        } catch (Exception $e) {
            return back()->with(['error' => $e->getMessage()]);
        }

        return back()->with(['success' => [__('Information updated successfully!')]]);
    }
}
