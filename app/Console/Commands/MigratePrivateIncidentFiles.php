<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Models\IncidentDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePrivateIncidentFiles extends Command
{
    protected $signature = 'raniag:migrate-private-files {--dry-run : List files without moving them}';

    protected $description = 'Move incident evidence and case documents from the public disk to the private local disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;

        $this->info($dryRun ? 'Dry run — no files will be moved.' : 'Moving files to private disk…');

        Evidence::query()->whereNotNull('file_path')->orderBy('id')->chunkById(100, function ($rows) use ($dryRun, &$moved, &$skipped) {
            foreach ($rows as $row) {
                $result = $this->movePath($row->file_path, $dryRun);
                $result === 'moved' ? $moved++ : $skipped++;
            }
        });

        IncidentDocument::query()->whereNotNull('file_path')->orderBy('id')->chunkById(100, function ($rows) use ($dryRun, &$moved, &$skipped) {
            foreach ($rows as $row) {
                $result = $this->movePath($row->file_path, $dryRun);
                $result === 'moved' ? $moved++ : $skipped++;
            }
        });

        $this->info("Done. Moved: {$moved}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function movePath(string $path, bool $dryRun): string
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        if ($private->exists($path)) {
            if ($public->exists($path) && ! $dryRun) {
                $public->delete($path);
            }

            return 'skipped';
        }

        if (! $public->exists($path)) {
            return 'skipped';
        }

        if ($dryRun) {
            $this->line("would move: {$path}");

            return 'moved';
        }

        $private->put($path, $public->get($path));
        $public->delete($path);

        return 'moved';
    }
}
