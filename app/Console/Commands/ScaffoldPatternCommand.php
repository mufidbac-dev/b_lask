<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScaffoldPatternCommand extends Command
{
    protected $signature = 'scaffold:pattern';
    protected $description = 'Show active scaffold pattern and available patterns';

    public function handle(): int
    {
        $active = config('scaffold.pattern');
        $patterns = array_keys(config('scaffold.patterns', []));

        $this->info("Active scaffold pattern: {$active}");

        foreach ($patterns as $p) {
            $marker = $p === $active ? '*' : ' ';
            $this->line("{$marker} {$p}");
        }

        return self::SUCCESS;
    }
}
