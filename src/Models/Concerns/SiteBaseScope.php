<?php

namespace Wsmallnews\Support\Models\Concerns;

use Wsmallnews\Support\Features\Site\SiteContext;

/**
 * Data space of a model that has no partition of its own, such as a user or a
 * member: the base of the site it is used in. The model declares this itself,
 * so relation packages keep reading the pair from the model and never guess.
 */
trait SiteBaseScope
{
    /** @return array{scope_type: string, scope_id: int} */
    public function getScopeable(): array
    {
        return app(SiteContext::class)->scopeableOrDefault();
    }

    public function getScopeType(): string
    {
        return $this->getScopeable()['scope_type'];
    }

    public function getScopeId(): int
    {
        return $this->getScopeable()['scope_id'];
    }
}
