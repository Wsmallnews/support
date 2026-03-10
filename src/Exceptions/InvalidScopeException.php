<?php

namespace Wsmallnews\Support\Exceptions;

use Exception;

/**
 * Exception thrown when scope configuration is invalid or missing.
 */
class InvalidScopeException extends Exception
{
    /**
     * Create exception for missing scope type.
     */
    public static function missingType(string $configKey): self
    {
        return new self("Scope type is missing or empty in configuration key: {$configKey}");
    }

    /**
     * Create exception for missing scope ID.
     */
    public static function missingId(string $configKey): self
    {
        return new self("Scope ID is missing or invalid in configuration key: {$configKey}");
    }

    /**
     * Create exception for invalid scope configuration.
     */
    public static function invalidConfiguration(string $configKey, string $reason = ''): self
    {
        $message = "Invalid scope configuration for key: {$configKey}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }

        return new self($message);
    }

    /**
     * Create exception for missing configuration.
     */
    public static function configNotFound(string $configKey): self
    {
        return new self("Scope configuration not found for key: {$configKey}");
    }
}
