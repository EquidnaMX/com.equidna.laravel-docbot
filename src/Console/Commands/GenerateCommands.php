<?php

namespace Equidna\LaravelDocbot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCommands extends Command
{
    protected $signature   = 'docbot:commands';
    protected $description = 'List all custom Artisan commands defined in the project (excluding built-in Laravel/Artisan commands)';

    /**
     * List all custom Artisan commands defined in the project (excluding built-in Laravel/Artisan commands).
     *
     * @return int
     */
    public function handle(): int
    {
        $excludeNamespaces = config('docbot.exclude_namespaces', []);
        $excludeCommands   = config('docbot.exclude_commands', []);

        $allCommands      = $this->getApplication()->all();
        $projectCommands  = [];

        // Filter out built-in commands
        foreach ($allCommands as $name => $command) {
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

        $outputDir = rtrim(config('docbot.output_dir'), '/\\') . '/commands';
        $outputFile = $outputDir . '/project_commands.md';

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // If no custom commands, write empty file and return
        if (empty($projectCommands)) {
            $this->info('No custom project commands found.');
            file_put_contents($outputFile, "# Custom Project Artisan Commands\n\nNo custom project commands found.\n");
            return 0;
        }

        // Build markdown table
        $this->info('Custom Project Artisan Commands:');
        $md = "# Custom Project Artisan Commands\n\n";
        $md .= "| Command | Description |\n";
        $md .= "| ------- | ----------- |\n";
        foreach ($projectCommands as $name => $command) {
            $desc = $command->getDescription();
            $this->line("- <info>{$name}</info>: {$desc}");
            $md .= "| `{$name}` | {$desc} |\n";
        }

        file_put_contents($outputFile, $md);
        return 0;
    }
}
