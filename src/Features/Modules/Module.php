<?php

namespace Wsmallnews\Support\Features\Modules;

/**
 * Module descriptor. The id is the global address key for config, registries and the
 * HasModuleContext prop, so every module-level lookup uses the same value.
 */
final class Module
{
    public function __construct(
        public readonly string $id,
        public readonly string $namespace,
        public readonly ?string $plugin = null,
    ) {}
}
