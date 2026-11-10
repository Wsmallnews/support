<?php

namespace Wsmallnews\Support\Features\Site;

/**
 * Data ownership mode of a site.
 *
 * - Tenant: rows belong to the current tenant (team_id = tenant).
 * - Platform: rows belong to the platform itself (team_id is null).
 * - Aggregate: cross-tenant view (team_id is not null), scoped to one source site.
 */
enum SiteMode: string
{
    case Tenant = 'tenant';

    case Platform = 'platform';

    case Aggregate = 'aggregate';

    /** Whether this mode filters to a concrete tenant. */
    public function isTenant(): bool
    {
        return $this === self::Tenant;
    }
}
