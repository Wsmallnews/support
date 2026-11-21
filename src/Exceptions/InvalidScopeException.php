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
            "Component [{$class}] has no resolved scope. Register it on a site through the host plugin "
            . '(resources()/pages()) so the scope is derived from the site registration, and a consumer-built '
            . 'component must use CanBeConfigured (its base declares $configurationClass).'
        );
    }

    /** A registration-derived value (module id, ...) was requested on an unconfigured component. */
    public static function missingComponentConfiguration(string $class, string $what): self
    {
        return new self(
            "Component [{$class}] cannot resolve its {$what}: it was not registered with a configuration. "
            . 'Register it through the host plugin, or use CanBeConfigured on a consumer-built component.'
        );
    }

    /** A component's declared scope suffix and its explicit instance suffix disagree. */
    public static function scopeSuffixConflict(string $class, string $declared, string $given): self
    {
        return new self(
            "Component [{$class}] declares sub-scope [{$declared}], but was given [{$given}]. "
            . 'The declared suffix is part of the component identity and cannot be overridden; '
            . 'use a component without a declared suffix for another partition.'
        );
    }

    /** A container type and its rows resolved to different scopes. */
    public static function typeScopeMismatch(string $typeId, string $typeScope, string $requestedScope): self
    {
        return new self(
            "Type [{$typeId}] belongs to scope [{$typeScope}], but the component resolved [{$requestedScope}]. "
            . 'A container type and its rows must share one scope.'
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

    /** Frontend scope derivation was attempted inside a panel (no site is bound there). */
    public static function siteContextInPanel(string $class): self
    {
        return new self(
            "Panel query on [{$class}] cannot use siteContext(): panels carry no site. "
            . 'Use panelContext($this->getScopeable()) for the registration-declared scope.'
        );
    }

    /** Panel scope entry point called outside a panel request. */
    public static function panelContextOutsidePanel(): self
    {
        return new self(
            'panelContext() is only available inside a panel. '
            . 'On a frontend site use siteContext() or siteContext(\'sub-scope\'); '
            . 'in console/queue use forScopeable() or SiteContext::run().'
        );
    }

    /** An explicit-scope query was called without a scopeable pair. */
    public static function missingScopeable(string $reason): self
    {
        return new self("Missing scopeable: {$reason}.");
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
