<?php

namespace App\Console\Commands;

use App\Domain\Catalogue\WooCommerceImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportWooCommerceCommand extends Command
{
    protected $signature = 'catalogue:import-woocommerce
        {--url= : Base URL of the live WooCommerce site, e.g. https://gymdog.fitness}
        {--status=active : Status for imported products (active|draft)}
        {--skip-media : Do not download product images}
        {--dry-run : Import inside a transaction and roll back, reporting counts only}';

    protected $description = 'Import products (prices, options, variants, images) from the WooCommerce Store API.';

    public function handle(): int
    {
        $url = $this->option('url');

        if (! $url) {
            $this->error('Pass --url=https://your-woocommerce-site');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        // Media downloads aren't transactional, so a dry run never re-hosts.
        $rehostMedia = ! $this->option('skip-media') && ! $dryRun;

        $importer = new WooCommerceImporter($url, $rehostMedia, (string) $this->option('status'));
        $this->info(($dryRun ? '[dry run] ' : '')."Importing products from {$url} …");

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
            ['Products', 'Variants', 'Categories', 'Brands', 'Media'],
            [[$counts['products'], $counts['variants'], $counts['categories'], $counts['brands'], $counts['media']]],
        );

        if ($importer->needsAttention !== []) {
            $this->warn('Needs attention:');
            foreach (array_unique($importer->needsAttention) as $note) {
                $this->line("  - {$note}");
            }
        }

        $this->info($dryRun ? 'Dry run complete — nothing was written.' : 'Import complete.');

        return self::SUCCESS;
    }
}
