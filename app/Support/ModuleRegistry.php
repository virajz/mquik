<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ModuleRegistry
{
    /** @var array<string, array> */
    protected array $modules = [];

    public function register(string $name, array $manifest): void
    {
        $this->modules[$name] = array_merge([
            'name' => $name,
            'label' => $name,
            'description' => null,
            'group' => 'General',
            'icon' => 'cube',
            'permissions' => [],
            'path' => null,
        ], $manifest);
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function get(string $name): ?array
    {
        return $this->modules[$name] ?? null;
    }

    public function all(): Collection
    {
        return collect($this->modules);
    }
}
