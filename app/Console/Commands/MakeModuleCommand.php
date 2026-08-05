<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make {name : Module name in StudlyCase, e.g. Catalog}';

    protected $description = 'Scaffold an empty module folder tree under app/Modules';

    protected const DIRECTORIES = [
        'Database/Migrations',
        'Database/Factories',
        'Database/Seeders',
        'Models',
        'Http/Controllers',
        'Http/Requests',
        'Http/Resources',
        'Policies',
        'Providers',
        'Routes',
        'Services',
        'tests/Feature',
        'tests/Unit',
    ];

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $base = app_path("Modules/{$name}");

        if (is_dir($base)) {
            $this->error("Module [{$name}] already exists at {$base}.");

            return self::FAILURE;
        }

        foreach (self::DIRECTORIES as $dir) {
            $path = "{$base}/{$dir}";
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
                file_put_contents("{$path}/.gitkeep", '');
            }
        }

        $providerStub = str_replace(
            '{{name}}',
            $name,
            file_get_contents(base_path('stubs/module/ServiceProvider.stub'))
        );
        file_put_contents("{$base}/Providers/{$name}ServiceProvider.php", $providerStub);

        $routesStub = str_replace(
            '{{name}}',
            $name,
            file_get_contents(base_path('stubs/module/routes.stub'))
        );
        file_put_contents("{$base}/Routes/api.php", $routesStub);

        unlink("{$base}/Providers/.gitkeep");
        unlink("{$base}/Routes/.gitkeep");

        $this->info("Module [{$name}] scaffolded at app/Modules/{$name}.");
        $this->line("Register App\\Modules\\{$name}\\Providers\\{$name}ServiceProvider::class in bootstrap/providers.php to activate it.");

        return self::SUCCESS;
    }
}
