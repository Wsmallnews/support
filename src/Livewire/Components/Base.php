<?php

namespace Wsmallnews\Support\Livewire\Components;

use Wsmallnews\Support\Livewire\Base as BaseComponent;
use Wsmallnews\Support\Livewire\Concerns\ResolvesModule;

/**
 * Package-internal base for support's own embeddable components.
 *
 * Same role as each domain package's Components\Base: site-bound plus module
 * resolution, for this directory only. The public extension point remains
 * Wsmallnews\Support\Livewire\Base.
 *
 * Composition is keyed by module, so getModuleId() (site owner by default) is
 * needed there. Search reads are site-keyed and derive the module themselves.
 * Embedding without a site and without an explicit module fails closed. */
abstract class Base extends BaseComponent
{
    use ResolvesModule;
}
