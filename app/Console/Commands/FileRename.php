<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FileRename extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan file:rename "app/Http/Helpers/Api/helpers.php" "app/Http/Helpers/Api/Helpers.php"
     */
    protected $signature = 'file:rename {oldPath} {newPath}';

    protected $description = 'Rename any file dynamically (supports case-only rename on Windows)';

    public function handle()
    {
        $oldPath = base_path($this->argument('oldPath'));
        $newPath = base_path($this->argument('newPath'));

        if (!file_exists($oldPath)) {
            $this->error("File not found: {$oldPath}");
            return 1;
        }

        // Case-only rename fix for Windows (NTFS is case-insensitive)
        if (strtolower($oldPath) === strtolower($newPath)) {
            $tempPath = $newPath . '.temp_rename';

            if (!rename($oldPath, $tempPath)) {
                $this->error("Failed to rename (step 1) {$oldPath} → {$tempPath}");
                return 1;
            }

            if (!rename($tempPath, $newPath)) {
                $this->error("Failed to rename (step 2) {$tempPath} → {$newPath}");
                return 1;
            }

            $this->info("Rename successful: {$oldPath} → {$newPath}");
            return 0;
        }

        // Normal rename
        if (file_exists($newPath)) {
            $this->error("Target file already exists: {$newPath}");
            return 1;
        }

        if (rename($oldPath, $newPath)) {
            $this->info("Renamed: {$oldPath} → {$newPath}");
            return 0;
        }

        $this->error("Failed to rename file.");
        return 1;
    }
}
