<?php

namespace App\Console\Commands;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\ManifestBuilder;
use Illuminate\Console\Command;

/** Regenerates the committed pack manifest from the files on disk (specs/10 §11.2 step 2). */
class AssetsBuildManifest extends Command
{
    protected $signature = 'assets:build-manifest {path? : Pack directory (defaults to config assets.pack_path)} {--check : Fail if the committed manifest is out of date instead of writing it}';

    protected $description = 'Generate the game asset manifest (checksums, byte sizes, slugs) from the curated pack files';

    public function handle(ManifestBuilder $builder): int
    {
        $dir = rtrim((string) ($this->argument('path') ?? config('assets.pack_path')), '/');
        $file = $dir.'/manifest.json';

        try {
            $json = $builder->encode($builder->build($dir));
        } catch (AssetPackException $e) {
            $this->error("Manifest build failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($this->option('check')) {
            if (! is_file($file) || (string) file_get_contents($file) !== $json) {
                $this->error('manifest.json is out of date — run php artisan assets:build-manifest and commit it.');

                return self::FAILURE;
            }
            $this->info('manifest.json is up to date.');

            return self::SUCCESS;
        }

        file_put_contents($file, $json);
        $this->info('Wrote '.substr_count($json, '"key"').' assets to manifest.json.');

        return self::SUCCESS;
    }
}
