<?php

namespace Wsmallnews\Support\Features\Search\Engines;

/**
 * Engines that accept per-module search options; undeclared keys fall back to
 * sn-support.search.*. Optional: implement it only when the engine needs the options.
 */
interface ConfigurableEngine extends Engine
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function setSearchConfig(array $config): static;
}
