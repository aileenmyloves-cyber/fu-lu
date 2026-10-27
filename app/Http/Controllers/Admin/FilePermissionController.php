<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\FilePermissionHelper;
use App\Http\Helpers\Response;

class FilePermissionController extends Controller
{
    /**
     * Display current file/folder permission status.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = 'File Permission';
        $permissions = FilePermissionHelper::overview();

        return view('admin.sections.file-permission.index', compact(
            'page_title',
            'permissions',
        ));
    }

    /**
     * Restore project file/folder permissions to their expected state.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore()
    {
        $result = FilePermissionHelper::fix();

        if ($result['failed'] > 0) {
            return redirect()->route('admin.file.permission.index')
                ->with(['warning' => [__('Permissions restored with some items skipped! (:fixed updated, :failed skipped)', ['fixed' => $result['fixed'], 'failed' => $result['failed']])]])
                ->with('permission_failures', $result['failures']);
        }

        return redirect()->route('admin.file.permission.index')
            ->with(['success' => [__('Permissions restored successfully! (:fixed items updated)', ['fixed' => $result['fixed']])]]);
    }

    /**
     * Scan the project for files/folders with incorrect permissions,
     * without changing anything on disk.
     *
     * @return \Illuminate\Http\Response
     */
    public function scan()
    {
        $result = FilePermissionHelper::scan(100);

        return Response::success([__('Scan completed!')], [
            'total' => $result['total'],
            'items' => $result['items'],
            'truncated' => $result['truncated'],
            'hidden_count' => $result['hidden_count'],
        ], 200);
    }

    /**
     * Delete public/storage (if it exists) and recreate it fresh, pointing
     * at storage/app/public. Fixes broken/stale symlinks.
     *
     * @return \Illuminate\Http\Response
     */
    public function relinkStorage()
    {
        if (! FilePermissionHelper::relinkStorage()) {
            return redirect()->route('admin.file.permission.index')
                ->with(['error' => [__('Failed to relink storage. Please try again.')]]);
        }

        return redirect()->route('admin.file.permission.index')
            ->with(['success' => [__('Storage relinked successfully!')]]);
    }
}
