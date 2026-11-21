<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;

/**
 * Route names the caller lends to a reusable component.
 *
 * A component cannot know the consuming site's route names — cms's `posts.show`
 * does not exist on a shop site — so the caller declares them per link key and
 * the component only resolves them against the bound site. One component may
 * need several: `['detail' => 'posts.show', 'index' => 'posts']`.
 *
 * Without a name for the key nothing is rendered: no guessed links.
 * Requires ScopedBySite (every Support Livewire component has it).
 */
trait HasCustomRoutes
{
    /** Route name per link key, e.g. ['detail' => 'posts.show']. */
    #[Locked]
    public array $customRoutes = [];

    /** URL of one link key; null when the caller did not declare it. */
    public function routeUrl(string $key, mixed $parameters = []): ?string
    {
        $name = $this->routeName($key);

        return $name === null ? null : $this->site()?->route($name, $parameters);
    }

    /** Route name of one link key (for components that hand it to another one). */
    public function routeName(string $key): ?string
    {
        return $this->customRoutes[$key] ?? null;
    }
}
