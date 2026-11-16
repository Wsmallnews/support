<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant context for Filament pages and widgets that build their own queries.
 *
 * Resource queries get the panel tenancy global scope for free; custom pages and
 * widgets do not, so they resolve the tenant here and fail closed when the panel
 * is tenant-scoped but no tenant is active (instead of returning every tenant's rows).
 */
trait TenantAware
{
    /** Current tenant key; null when the panel is not tenant-scoped or the component opts out. */
    public static function getTenantId(): int | string | null
    {
        if (! static::panelUsesTenancy()) {
            return null;
        }

        if (method_exists(static::class, 'isScopedToTenant') && ! static::isScopedToTenant()) {
            return null;
        }

        return Filament::getTenant()?->getKey();
    }

    /** Panel tenancy on, component tenant-scoped, but no tenant is active. */
    public static function isTenantContextMissing(): bool
    {
        if (! static::panelUsesTenancy()) {
            return false;
        }

        $scoped = method_exists(static::class, 'isScopedToTenant')
            ? static::isScopedToTenant()
            : true;

        return $scoped && Filament::getTenant() === null;
    }

    /**
     * Fail closed on queries that already carry tenant scoping on their own
     * (e.g. nestedset scoped queries built from the model's scope attributes).
     */
    protected static function guardTenantContext(Builder $query): Builder
    {
        return static::isTenantContextMissing() ? $query->whereRaw('1 = 0') : $query;
    }

    /** The current panel declares tenancy — the only source of "tenancy is on" in the admin. */
    protected static function panelUsesTenancy(): bool
    {
        return (bool) Filament::getCurrentPanel()?->hasTenancy();
    }
}
