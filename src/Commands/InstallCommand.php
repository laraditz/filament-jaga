<?php

namespace Laraditz\FilamentJaga\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'jaga:install
                            {--assign : Only assign the super-admin role to a user (skip seeding)}
                            {--email= : Email of the user to assign the super-admin role to}
                            {--force : Re-publish config even if it already exists}';

    protected $description = 'Install and set up filament-jaga (seeds permission, role, and optionally assigns a user)';

    public function handle(): int
    {
        // Full implementation in Task 6
        return self::SUCCESS;
    }
}
