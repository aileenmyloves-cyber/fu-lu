<?php

namespace App\Http\Helpers;

use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FilePermissionHelper
{
    /**
     * Delete whatever currently sits at public/storage - symlink (valid or
     * broken), a real directory, or a stray file - and recreate it fresh
     * via `storage:link`.
     *
     * @return bool true if public/storage is a working symlink afterwards
     */
    public static function relinkStorage(): bool
    {
        $link = public_path('storage');

        // file_exists() follows symlinks, so this only catches a real
        // (non-symlink) file/directory blocking the path.
        if (file_exists($link) && ! is_link($link)) {
            File::deleteDirectory($link);
        }

        if (is_link($link)) {
            @unlink($link);
        }

        Artisan::call('storage:link', ['--force' => true]);

        return is_link($link);
    }

    /**
     * Build a read-only snapshot of the permission state for the paths that
     * actually matter to a running Laravel app, for display on the File
     * Permission admin page.
     *
     * @return array<int, array{label: string, path: string, exists: bool, type: ?string, permission: ?string, status: string, status_message: string}>
     */
    public static function overview()
    {
        $base_path = base_path();

        $rows = [];

        $writable_paths = [
            'storage' => 'storage',
            'storage/app' => 'storage/app',
            'storage/framework' => 'storage/framework',
            'storage/framework/cache' => 'storage/framework/cache',
            'storage/framework/sessions' => 'storage/framework/sessions',
            'storage/framework/views' => 'storage/framework/views',
            'storage/logs' => 'storage/logs',
            'bootstrap/cache' => 'bootstrap/cache',
        ];
        foreach ($writable_paths as $label => $relative) {
            $rows[] = self::buildOverviewRow($base_path, $label, $relative, 'writable');
        }

        $sensitive_paths = [
            '.env' => '.env',
            'storage/oauth-public.key' => 'storage/oauth-public.key',
            'storage/oauth-private.key' => 'storage/oauth-private.key',
        ];
        foreach ($sensitive_paths as $label => $relative) {
            if (! file_exists($base_path.'/'.$relative)) {
                continue;
            }
            $rows[] = self::buildOverviewRow($base_path, $label, $relative, 'sensitive');
        }

        $info_paths = [
            'Project Root' => '',
            'app' => 'app',
            'public' => 'public',
            'public/storage' => 'public/storage',
        ];
        foreach ($info_paths as $label => $relative) {
            $rows[] = self::buildOverviewRow($base_path, $label, $relative, 'info');
        }

        return self::sortOverviewRows($rows);
    }

    /**
     * Fix project file/folder permissions.
     *
     * Works without root/sudo so it can run from a shared-hosting PHP process:
     * ownership (chown) is intentionally left alone since a non-privileged
     * process usually can't change it anyway.
     *
     * @return array{fixed: int, failed: int, failures: array<int, array{path: string, reason: string}>}
     */
    public static function fix()
    {
        set_time_limit(300);

        $base_path = base_path();
        $fixed = 0;
        $failures = [];

        self::walkTargets(function ($path, $mode) use (&$fixed, &$failures, $base_path) {
            error_clear_last();

            if (@chmod($path, $mode)) {
                $fixed++;

                return;
            }

            $error = error_get_last();
            $reason = $error['message'] ?? null;

            if (! $reason) {
                $reason = is_link($path)
                    ? __('Symlink permissions follow their target and usually cannot be changed directly')
                    : __('Unknown error while changing permission');
            }

            $failures[] = [
                'path' => str_replace($base_path.DIRECTORY_SEPARATOR, '', $path),
                'reason' => $reason,
            ];
        });

        return [
            'fixed' => $fixed,
            'failed' => count($failures),
            'failures' => $failures,
        ];
    }

    /**
     * Read-only scan for files/folders whose current permission doesn't match
     * what fix() would set. Nothing on disk is changed.
     *
     * @return array{total: int, items: array<int, array{path: string, type: string, current: string, expected: string}>, truncated: bool, hidden_count: int}
     */
    public static function scan($limit = 100)
    {
        set_time_limit(300);

        $base_path = base_path();
        $items = [];
        $total = 0;

        self::walkTargets(function ($path, $expected_mode) use (&$items, &$total, $base_path, $limit) {
            // A symlink's own mode is largely meaningless (usually always
            // rwxrwxrwx) and chmod on it really targets the link's target,
            // so it's excluded from "wrong permission" reporting.
            if (is_link($path)) {
                return;
            }

            $current_mode = @fileperms($path);
            if ($current_mode === false) {
                return;
            }
            $current_mode &= 0777;

            if ($current_mode === $expected_mode) {
                return;
            }

            $total++;

            if (count($items) >= $limit) {
                return;
            }

            $items[] = [
                'path' => str_replace($base_path.DIRECTORY_SEPARATOR, '', $path),
                'type' => is_dir($path) ? 'directory' : 'file',
                'current' => substr(sprintf('%o', $current_mode), -4),
                'expected' => substr(sprintf('%o', $expected_mode), -4),
            ];
        });

        return [
            'total' => $total,
            'items' => $items,
            'truncated' => $total > count($items),
            'hidden_count' => max(0, $total - count($items)),
        ];
    }

    /**
     * Walk every project path considered by the permission fixer/scanner and
     * call $handler($path, $expected_mode) for each one. Shared by fix()
     * (which chmod's) and scan() (which only reads), so both stay in sync
     * about what "correct" means.
     *
     * @param  callable(string, int): void  $handler
     */
    private static function walkTargets(callable $handler)
    {
        $base_path = base_path();
        // Relative paths handled by the dedicated writable-dir pass below, or
        // that should never be touched (vendor/node_modules/.git are huge and
        // composer/npm/git already manage their own permissions).
        $excluded_paths = ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache'];

        // Collect into path => mode first (later assignments override earlier
        // ones) so a path only gets one final expected mode even though it can
        // be covered by more than one pass below (e.g. .env and the Passport
        // keys also fall under the general sweep before their override runs).
        $targets = [];

        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($base_path, FilesystemIterator::SKIP_DOTS),
            function ($current) use ($base_path, $excluded_paths) {
                if (! $current->isDir()) {
                    return true;
                }

                $relative_path = str_replace($base_path.DIRECTORY_SEPARATOR, '', $current->getPathname());

                return ! in_array($relative_path, $excluded_paths, true);
            }
        );

        $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $targets[$item->getPathname()] = $item->isDir() ? 0755 : 0644;
        }

        // storage & bootstrap/cache must stay writable by the web server
        foreach (['storage', 'bootstrap/cache'] as $writable_dir) {
            $writable_path = $base_path.'/'.$writable_dir;
            if (! is_dir($writable_path)) {
                continue;
            }

            $targets[$writable_path] = 0775;

            $writable_iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($writable_path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($writable_iterator as $item) {
                $targets[$item->getPathname()] = $item->isDir() ? 0775 : 0664;
            }
        }

        // .env holds secrets, keep it out of the world-readable 0644 sweep above
        $env_path = $base_path.'/.env';
        if (file_exists($env_path)) {
            $targets[$env_path] = 0664;
        }

        // Passport RSA keys, if present, should stay owner/group only
        foreach (['storage/oauth-public.key', 'storage/oauth-private.key'] as $key_file) {
            $key_path = $base_path.'/'.$key_file;
            if (file_exists($key_path)) {
                $targets[$key_path] = 0660;
            }
        }

        foreach ($targets as $path => $mode) {
            $handler($path, $mode);
        }
    }

    /**
     * Project Root first, then everything else A-Z, with hidden files/folders
     * (dotfiles like .env) pushed to the very end.
     *
     * @param  array<int, array{label: string, path: string}>  $rows
     * @return array<int, array>
     */
    private static function sortOverviewRows(array $rows)
    {
        $root = null;
        $normal = [];
        $hidden = [];

        foreach ($rows as $row) {
            if ($row['path'] === '.') {
                $root = $row;

                continue;
            }

            if (str_starts_with(basename($row['path']), '.')) {
                $hidden[] = $row;
            } else {
                $normal[] = $row;
            }
        }

        $by_path = fn ($a, $b) => strcasecmp($a['path'], $b['path']);
        usort($normal, $by_path);
        usort($hidden, $by_path);

        $sorted = array_merge($normal, $hidden);
        if ($root) {
            array_unshift($sorted, $root);
        }

        return $sorted;
    }

    /**
     * @return array{label: string, path: string, exists: bool, type: ?string, permission: ?string, status: string, status_message: string}
     */
    private static function buildOverviewRow($base_path, $label, $relative, $check_type)
    {
        $full_path = $relative === '' ? $base_path : $base_path.'/'.$relative;

        // is_link() checks the directory entry itself, so it still finds a
        // symlink even when its target is missing (file_exists() follows the
        // link and would wrongly report a broken symlink as "not found").
        $is_link = is_link($full_path);

        if (! $is_link && ! file_exists($full_path)) {
            return [
                'label' => $label,
                'path' => $relative ?: '.',
                'exists' => false,
                'type' => null,
                'permission' => null,
                'status' => 'warning',
                'status_message' => __('Path not found'),
            ];
        }

        if ($is_link) {
            $link_target = readlink($full_path);
            $target_exists = file_exists($full_path); // follows the link, so this reflects the target
            $lstat = lstat($full_path);
            $permission = $lstat ? substr(sprintf('%o', $lstat['mode'] & 0777), -4) : null;

            return [
                'label' => $label,
                'path' => $relative ?: '.',
                'exists' => true,
                'type' => 'symlink',
                'permission' => $permission,
                'status' => $target_exists ? 'ok' : 'warning',
                'status_message' => $target_exists
                    ? __('Symlink to :target', ['target' => $link_target])
                    : __('Broken symlink, target not found: :target', ['target' => $link_target]),
            ];
        }

        $permission = substr(sprintf('%o', fileperms($full_path)), -4);
        $type = is_dir($full_path) ? 'directory' : 'file';

        $status = 'ok';
        $status_message = __('Looks good');

        if ($check_type === 'writable' && ! is_writable($full_path)) {
            $status = 'warning';
            $status_message = __('Not writable by the web server');
        } elseif ($check_type === 'sensitive' && (octdec($permission) & 0044)) {
            $status = 'warning';
            $status_message = __('Readable by others, consider restricting access');
        }

        return [
            'label' => $label,
            'path' => $relative ?: '.',
            'exists' => true,
            'type' => $type,
            'permission' => $permission,
            'status' => $status,
            'status_message' => $status_message,
        ];
    }
}
