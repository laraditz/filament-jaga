<?php

use Laraditz\FilamentJaga\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class)->in('Feature');

/**
 * Helper to test Livewire components (replaces pest-plugin-livewire).
 *
 * @param  class-string  $component
 * @param  array<string, mixed>  $params
 */
function livewire(string $component, array $params = []): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test($component, $params);
}
