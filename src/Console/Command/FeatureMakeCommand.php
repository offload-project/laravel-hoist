<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Console\Command;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Laravel\Pennant\Commands\FeatureMakeCommand as BaseFeatureMakeCommand;

final class FeatureMakeCommand extends BaseFeatureMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hoist:feature';

    /**
     * Get the configured feature directories map.
     *
     * @return array<string, string>
     */
    protected function getDirectoryConfig(): array
    {
        $config = config('hoist.feature_directories', []);

        if (is_array($config) && count($config) > 0) {
            return $config;
        }

        return [app_path('Features') => 'App\\Features'];
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $config = $this->getDirectoryConfig();
        $namespace = reset($config);

        // If the namespace starts with the root namespace, return the full namespace
        // Otherwise, append it to the root namespace
        if (str_starts_with($namespace, $rootNamespace)) {
            return $namespace;
        }

        return $rootNamespace.'\\'.$namespace;
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        $directoryMap = $this->getDirectoryConfig();
        $baseDirectory = array_key_first($directoryMap);
        $namespace = $directoryMap[$baseDirectory];

        // Remove the namespace from the class name to get the relative path
        $name = Str::replaceFirst($namespace, '', $name);
        $name = mb_ltrim(str_replace('\\', '/', $name), '/');

        return $baseDirectory.'/'.$name.'.php';
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceTag($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     */
    protected function replaceTag(string &$stub, string $name): string
    {
        $name = str_replace($this->getNamespace($name).'\\', '', $name);

        $searches = [
            ['{{ kebab }}', '{{ label }}', '{{ class }}'],
            ['{{kebab}}', '{{label}}', '{{class}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [Str::kebab($name), Str::headline($name), Str::studly($name)],
                $stub
            );
        }

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $publishedStub = base_path('stubs/hoist-feature/hoist-feature.stub');

        if (file_exists($publishedStub)) {
            return $publishedStub;
        }

        return __DIR__.'/../../../stubs/hoist-feature.stub';
    }
}
