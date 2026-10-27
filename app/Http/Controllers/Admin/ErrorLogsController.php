<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class ErrorLogsController extends Controller
{
    /**
     * Method for view error logs page
     * @return view
     */
    public function index(){
        $page_title         = __("Error Logs");

        $log_file = storage_path('logs/laravel.log');

        if (!File::exists($log_file)) {
            return view('admin.sections.error-logs.index', ['logs' => []]);
        }

        $log_content = File::get($log_file);

        $lines = explode("\n", $log_content);

        $logs   = [];
        $entry  = [];

        foreach ($lines as $line) {
            if (preg_match("/^\[(.*?)\].*/", $line)) {
                if (!empty($entry)) {
                    $logs[] = implode("\n", $entry);
                    $entry = [];
                }
            }
            $entry[] = $line;
        }

        if (!empty($entry)) {
            $logs[] = implode("\n", $entry);
        }

        $logs = array_reverse($logs);

        return view('admin.sections.error-logs.index', compact('page_title','logs'));

    }
    /**
     * Method for clear error logs
     */
    public function clear()
    {
        $logFile = storage_path('logs/laravel.log');

        if (File::exists($logFile)) {
            File::put($logFile, ''); // Clear file contents
            Session::flash('success', __('Log file cleared successfully.'));
        } else {
            Session::flash('error', __('Log file does not exist.'));
        }

        return redirect()->route('admin.error.logs.index')->with(['success' => [__('Error logs cleared successfully.')]]);
    }
}
