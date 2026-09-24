<?php

use Symfony\Component\Finder\Finder;

// specs/10 §2.1: swapping MinIO → R2 is an .env change. Application code always resolves the disk
// through config('media.disk') and never hardcodes a disk name, bucket or endpoint, so no provider
// literal lives outside config/. (Comments may name providers to explain intent.)
it('hardcodes no storage disk, bucket or endpoint in application code', function () {
    $files = Finder::create()->files()->name('*.php')->in(dirname(__DIR__, 2).'/app');

    foreach ($files as $file) {
        $contents = $file->getContents();

        expect($contents)->not->toMatch("/disk\(\s*'(r2|s3|minio)'\s*\)/");
        expect($contents)->not->toContain('clashcommons-media');
        expect($contents)->not->toContain('r2.cloudflarestorage.com');
    }
});
