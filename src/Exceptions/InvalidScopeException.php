<?php

namespace Wsmallnews\Support\Exceptions;

use Exception;

/** Exception thrown when scope configuration is invalid or missing. */
class InvalidScopeException extends Exception
{
    /** Create exception for missing scope type. */
    public static function missingType(string $configKey): self
    {
        return new self("Scope type is missing or empty in configuration key: {$configKey}");
    }

    /** Create exception for missing scope ID. */
    public static function missingId(string $configKey): self
    {
        return new self("Scope ID is missing or invalid in configuration key: {$configKey}");
    }

    /** Create exception for invalid scope configuration. */
    public static function invalidConfiguration(string $configKey, string $reason = ''): self
    {
        $message = "Invalid scope configuration for key: {$configKey}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }

        return new self($message);
    }

    /** Create exception for a site without a resolved scopeable. */
    public static function missingSiteScopeable(string $siteId): self
    {
        return new self("Site [{$siteId}] has no scopeable.");
    }

    /** Create exception for an aggregate site referencing an unknown source. */
    public static function missingSourceSite(string $siteId, string $sourceSiteId): self
    {
        return new self("Site [{$siteId}] references unknown source site [{$sourceSiteId}].");
    }

    /** Component was introduced directly on a panel without a declared scope. */
    public static function missingSiteBinding(string $class): self
    {
        return new self(
            "Component [{$class}] has no scope: it was not registered for a site and declares no scopeable. "
            . "When introducing it directly on a panel, pass ['scopeable' => ['scope_type' => ..., 'scope_id' => ...]]."
        );
    }

    /** No registered component on this site declares the requested sub-scope. */
    public static function unknownScopeSuffix(string $siteId, string $suffix, array $available): self
    {
        $known = $available === [] ? 'none' : implode(', ', $available);

        return new self(
            "Site [{$siteId}] has no sub-scope [{$suffix}]. Declared sub-scopes: {$known}. "
            . 'Declare it on the component class via getScopeSuffix() or a scope_suffix override.'
        );
    }

    /** Create exception for missing configuration. */
    public static function configNotFound(string $configKey): self
    {
        return new self("Scope configuration not found for key: {$configKey}");
    }

    /** Create exception for referencing a scopeable instance that is not declared. */
    public static function unknownInstance(string $configKey, string $key): self
    {
        return new self("Unknown scopeable instance [{$key}] in configuration key: {$configKey}");
    }
}
