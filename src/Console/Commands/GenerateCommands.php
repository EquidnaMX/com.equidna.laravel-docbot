<?php

namespace Equidna\LaravelDocbot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCommands extends Command
{

    protected $signature   = 'docbot:commands';
    protected $description = 'List all custom Artisan commands defined in the project (excluding built-in Laravel/Artisan commands)';

    public function handle(): int
    {
        $excludeNamespaces = [
            'app:',
            'auth:',
            'breeze:',
            'cache:',
            'channel:',
            'completion',
            'config:',
            'db:',
            'debugbar:',
            'event:',
            'foundation:',
            'fortify:',
            'help',
            'horizon:',
            'ide-helper:',
            'install:',
            'jetstream:',
            'key:',
            'lang:',
            'list',
            'mail:',
            'make:',
            'migrate:',
            'model:',
            'notifications:',
            'nova:',
            'octane:',
            'optimize',
            'package:',
            'passport:',
            'policy:',
            'preset:',
            'queue:',
            'route:',
            'sanctum:',
            'scout:',
            'schema:',
            'seed',
            'serve',
            'session:',
            'socialite:',
            'spark:',
            'storage:',
            'stub:',
            'tinker',
            'test',
            'ui:',
            'vendor:',
            'view:',
        ];

        $excludeCommands = [
            '_complete',
            'about',
            'clear-compiled',
            'db',
            'docs',
            'down',
            'invoke-serialized-closure',
            'migrate',
            'up',
        ];

        $allCommands = $this->getApplication()->all();
        $projectCommands = [];

        foreach ($allCommands as $name => $command) {
            // Exclude built-in commands by prefix or exact name
            $isBuiltin = false;
            foreach ($excludeNamespaces as $prefix) {
                if (Str::startsWith($name, $prefix)) {
                    $isBuiltin = true;
                    break;
                }
            }
            if (in_array($name, $excludeCommands, true)) {
                $isBuiltin = true;
            }
            if (!$isBuiltin) {
                $projectCommands[$name] = $command;
            }
        }

        if (empty($projectCommands)) {
            $this->info('No custom project commands found.');
            // Also write an empty file
            $dir = rtrim(config('docbot.output_dir'), '/\\') . '/commands';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($dir . '/project_commands.md', "# Custom Project Artisan Commands\n\nNo custom project commands found.\n");
            return 0;
        }

        $this->info('Custom Project Artisan Commands:');
        $md = "# Custom Project Artisan Commands\n\n";
        $md .= "| Command | Description |\n";
        $md .= "| ------- | ----------- |\n";
        foreach ($projectCommands as $name => $command) {
            $desc = $command->getDescription();
            $this->line("- <info>{$name}</info>: {$desc}");
            $md .= "| `{$name}` | {$desc} |\n";
        }
        $dir = rtrim(config('docbot.output_dir'), '/\\') . '/commands';
        /**
         * Execute the console command.
         *
         * @return int
         */
    }
}
