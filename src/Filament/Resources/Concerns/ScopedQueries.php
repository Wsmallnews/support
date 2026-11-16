<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Support\Filament\Concerns\RequiresConfiguration;

/**
 * Scopes a resource query to the data space declared at registration.
 *
 * Carries the scope contract (RequiresConfiguration) so a resource base only
 * imports this one trait: the pair comes from the registration declaration and
 * fails closed when none was injected. CanBeConfigured on the concrete resource
 * overrides the contract with the configuration-backed implementation.
 *
 * Tenant handling is split with Filament: every resource model already carries
 * the panel's tenancy global scope, so adding our own tenant condition would
 * only duplicate the same predicate (and would defeat a resource that opted out
 * with isScopedToTenant(false)). The tenant rule is therefore applied only when
 * the panel has no tenancy — there it keeps the panel on platform rows instead
 * of leaking another tenant's data.
 */
trait ScopedQueries
{
    use RequiresConfiguration;

    protected static function applyScopeToQuery(Builder $query): Builder
    {
        return $query->forScopeable(
            static::getScopeable(),
            withTenant: ! Filament::getCurrentPanel()?->hasTenancy(),
        );
    }
}
