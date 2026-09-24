<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('escapes untrusted component text', function (string $template) {
    $payload = '<script>alert("x")</script>';
    $html = Blade::render($template, compact('payload'));
    expect($html)->not->toContain('<script>')->toContain('&lt;script&gt;');
})->with([
    '<x-ui.input id="safe" :label="$payload" :error="$payload" :value="$payload" />',
    '<x-ui.textarea id="safe" label="About" :value="$payload" />',
    '<x-ui.avatar :name="$payload" />',
    '<x-ui.empty-state :title="$payload" :body="$payload" />',
    '<x-ui.modal name="safe" :title="$payload">{{ $payload }}</x-ui.modal>',
    '<x-ui.toast :title="$payload">{{ $payload }}</x-ui.toast>',
    '<x-ui.tooltip :text="$payload">Help</x-ui.tooltip>',
    '<x-ui.select id="safe" label="Category" :searchable="true" :options="[1 => $payload]" />',
]);

it('rejects active-content and protocol-relative avatar URLs', function (string $src) {
    expect(fn () => Blade::render('<x-ui.avatar name="Example" :src="$src" />', ['src' => $src]))
        ->toThrow(ViewException::class, 'Avatar URL must be HTTP(S) or root-relative.');
})->with(['javascript:alert(1)', 'data:image/svg+xml,<svg onload="alert(1)">', '//untrusted.test/image.png']);
