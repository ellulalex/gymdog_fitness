<?php

namespace App\Console\Commands;

use App\Domain\Content\WordPressImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportWordPressCommand extends Command
{
    protected $signature = 'content:import-wordpress
        {--url= : Base URL of the live WordPress site, e.g. https://gymdog.fitness}
        {--skip-media : Leave image URLs pointing at WordPress instead of re-hosting them}
        {--dry-run : Import inside a transaction and roll back, reporting counts only}';

    protected $description = 'Import pages and posts from a live WordPress site into the content tables.';

    public function handle(): int
    {
        $url = $this->option('url');

        if (! $url) {
            $this->error('Pass --url=https://your-wordpress-site');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[dry run] ' : '')."Importing from {$url} …");

        $importer = new WordPressImporter($url, rehostMedia: ! $this->option('skip-media'));

        try {
            if ($dryRun) {
                DB::beginTransaction();
                $counts = $importer->import();
                DB::rollBack();
            } else {
                $counts = DB::transaction(fn () => $importer->import());
            }
        } catch (Throwable $e) {
            if ($dryRun) {
                DB::rollBack();
            }
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Pages', 'Guides', 'Posts', 'Categories', 'Redirects', 'Media', 'Skipped'],
            [[$counts['pages'], $counts['guides'], $counts['posts'], $counts['categories'], $counts['redirects'], $counts['media'], $counts['skipped']]],
        );

        $this->info($dryRun ? 'Dry run complete — nothing was written.' : 'Import complete.');

        if ($this->option('skip-media')) {
            $this->comment('Media re-hosting skipped — image URLs still point at WordPress.');
        } else {
            $this->comment('Images re-hosted to storage/app/public/content-media (run `php artisan storage:link` once if not already linked).');
        }

        return self::SUCCESS;
    }
}
