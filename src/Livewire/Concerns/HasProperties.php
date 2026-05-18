<?php

namespace Wsmallnews\Support\Livewire\Concerns;

trait HasProperties
{
    public ?array $properties = [];


    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(?string $key = null, $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    public function setProperty(string $key, mixed $value): static
    {
        $this->properties[$key] = $value;

        return $this;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }
}
