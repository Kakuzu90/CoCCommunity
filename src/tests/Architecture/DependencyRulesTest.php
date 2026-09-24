<?php

use Symfony\Component\Process\Process;

it('enforces module dependencies', function (string $source, string $target, bool $allowed) {
    $root = dirname(__DIR__, 2);
    $directory = sys_get_temp_dir().'/clashcommons-deptrac-'.bin2hex(random_bytes(8));
    mkdir($directory);
    $sourceNamespace = substr($source, 0, strrpos($source, '\\'));
    $sourceName = substr($source, strrpos($source, '\\') + 1);
    $targetNamespace = substr($target, 0, strrpos($target, '\\'));
    $targetName = substr($target, strrpos($target, '\\') + 1);

    file_put_contents($directory.'/Source.php', "<?php namespace {$sourceNamespace}; class {$sourceName} { public function run(\\{$target} \$target): void {} }");
    file_put_contents($directory.'/Target.php', "<?php namespace {$targetNamespace}; class {$targetName} {}");
    $config = str_replace(['./app', 'resource: deptrac.allowlist'], [$directory, 'resource: '.$root.'/deptrac.allowlist'], file_get_contents($root.'/deptrac.yaml'));
    file_put_contents($directory.'/deptrac.yaml', $config);

    try {
        $process = new Process([PHP_BINARY, $root.'/vendor/bin/deptrac', 'analyse', '--config-file='.$directory.'/deptrac.yaml', '--no-cache', '--no-progress', '--fail-on-uncovered'], $root);
        $process->run();
        expect($process->getExitCode())->toBe($allowed ? 0 : 1, $process->getOutput().$process->getErrorOutput());
        if (! $allowed) {
            expect($process->getOutput())->toContain('must not depend on');
        }
    } finally {
        foreach (glob($directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
})->with([
    'own model' => ['App\\Domain\\Bases\\Services\\Publisher', 'App\\Domain\\Bases\\Models\\Layout', true],
    'public service' => ['App\\Domain\\Bases\\Services\\Publisher', 'App\\Domain\\Users\\Services\\Profiles', true],
    'foreign model' => ['App\\Domain\\Bases\\Services\\Publisher', 'App\\Domain\\Users\\Models\\Profile', false],
    'domain to HTTP' => ['App\\Domain\\Bases\\Services\\Publisher', 'App\\Http\\Controllers\\Home', false],
    'support to domain' => ['App\\Support\\Helper', 'App\\Domain\\Users\\Services\\Profiles', false],
    'edge module' => ['App\\Domain\\Media\\Services\\Uploader', 'App\\Domain\\Users\\Services\\Profiles', false],
    'presentation action' => ['App\\Http\\Controllers\\Home', 'App\\Domain\\Bases\\Actions\\Publish', true],
    'foreign action' => ['App\\Domain\\Users\\Services\\Profiles', 'App\\Domain\\Bases\\Actions\\Publish', false],
]);
