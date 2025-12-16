<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use OffloadProject\Hoist\Console\Command\FeatureMakeCommand;

test('stub file contains correct placeholders', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('{{ namespace }}')
        ->and($content)->toContain('{{ class }}')
        ->and($content)->toContain('{{ kebab }}')
        ->and($content)->toContain('{{ label }}');
});

test('stub file contains strict types declaration', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('declare(strict_types=1);');
});

test('stub file implements Feature interface', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('use OffloadProject\\Hoist\\Contracts\\Feature;')
        ->and($content)->toContain('implements Feature');
});

test('stub file has resolve method with mixed scope parameter', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('public function resolve(mixed $scope): mixed');
});

test('stub file has metadata method', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('public function metadata(): array');
});

test('stub file has nullable properties with null defaults', function () {
    $stubPath = __DIR__.'/../../stubs/hoist-feature.stub';
    $content = File::get($stubPath);

    expect($content)->toContain('public ?string $description = null;')
        ->and($content)->toContain('public ?string $route = null;');
});

test('command class has correct name', function () {
    $command = new FeatureMakeCommand(app('files'));

    expect($command->getName())->toBe('hoist:feature');
});

test('command extends pennant feature make command', function () {
    expect(FeatureMakeCommand::class)
        ->toExtend(Laravel\Pennant\Commands\FeatureMakeCommand::class);
});
