<?php

namespace Wsmallnews\Support\Exceptions;

use InvalidArgumentException;

/** Exception thrown when site registration or site usage is invalid. */
class InvalidSiteException extends InvalidArgumentException
{
    /** A site id (plugin + key) may only be registered once, across all panels. */
    public static function alreadyRegistered(string $siteId, string $panelId): self
    {
        return new self(
            "Site [{$siteId}] is already registered by panel [{$panelId}]. "
            . 'A site key may only be declared once per plugin; use a different key instead of re-registering it.'
        );
    }

    /** Two frontend sites must not claim the same domain + prefix. */
    public static function duplicateAddress(string $siteId, string $otherSiteId, string $address): self
    {
        return new self(
            "Site [{$siteId}] uses the same frontend address [{$address}] as site [{$otherSiteId}]. "
            . 'Give each frontend site its own domain/prefix.'
        );
    }

    /** Aggregate sites must reference an existing, non-aggregate source. */
    public static function invalidSource(string $siteId, string $sourceSiteId): self
    {
        return new self(
            "Site [{$siteId}] references invalid source site [{$sourceSiteId}]. "
            . 'An aggregate site must point to a registered non-aggregate site.'
        );
    }

    /** Aggregate sites read across tenants and must not be written through. */
    public static function readOnly(string $siteId): self
    {
        return new self("Site [{$siteId}] is read-only because it aggregates other tenants.");
    }

    /** Site options belong to the module that owns the site; others must read static config. */
    public static function configOutsideSite(string $root, ?string $siteId): self
    {
        return new self(
            $siteId === null
                ? "Module site config [{$root}.options.*] requires a [{$root}] site context."
                : "Module site config [{$root}.options.*] cannot be read from site [{$siteId}]."
        );
    }

    /** An operation needs the current site, but none is bound (console, queue, panel). */
    public static function noCurrentSite(string $operation): self
    {
        return new self(
            "No current site is bound, so [{$operation}] cannot be resolved. "
            . 'Wrap the work in SiteContext::run($site, ...) or pass an explicit Site.'
        );
    }

    /** Two non-aggregate sites, in any plugin, must not share one data space. */
    public static function duplicateScopeable(string $siteId, string $otherSiteId, string $scopeType, int $scopeId): self
    {
        return new self(
            "Sites [{$siteId}] and [{$otherSiteId}] declare the same scopeable [{$scopeType}:{$scopeId}]. "
            . 'Give each site its own scope, or use an aggregate site to read across sites.'
        );
    }

    /** Multi-instance registrations need one explicit key per extra instance. */
    public static function missingInstanceKey(string $siteId, string $class): self
    {
        return new self(
            "Component [{$class}] is registered more than once on site [{$siteId}] without explicit keys. "
            . "Pass a distinct 'key' for every additional registration."
        );
    }

    /** Every extra instance must name its partition. */
    public static function missingInstanceScopeSuffix(string $siteId, string $class): self
    {
        return new self(
            "Component [{$class}] is registered more than once on site [{$siteId}] and more than one instance "
            . "omits 'scope_suffix'. Every additional instance must declare its own sub-scope."
        );
    }

    /** One site cannot register the same class under the same configuration key twice. */
    public static function duplicateConfiguration(string $siteId, string $class, string $key): self
    {
        return new self(
            "Component [{$class}] is registered twice on site [{$siteId}] with configuration key [{$key}]. "
            . "Pass a distinct 'key' override for each registration."
        );
    }
}
