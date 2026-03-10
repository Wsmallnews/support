<?php

namespace Wsmallnews\Support\Data;

use Wsmallnews\Support\Exceptions\InvalidScopeException;

/**
 * Value object representing a scope context.
 * Provides type safety and validation for scope_type and scope_id.
 */
class ScopeableContext
{
    /**
     * Create a new scope context.
     *
     * @param  string  $scopeType  The scope type identifier
     * @param  int  $scopeId  The scope ID (default: 0 for global scope)
     */
    public function __construct(
        public readonly string $scopeType,
        public readonly int $scopeId = 0
    ) {
        $this->validate();
    }

    /**
     * Create from array.
     *
     * @param  array  $data  Array with 'scope_type' and 'scope_id' keys
     * @return self
     *
     * @throws InvalidScopeException
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['scope_type']) || blank($data['scope_type'])) {
            throw InvalidScopeException::missingType('array');
        }

        if (! isset($data['scope_id'])) {
            throw InvalidScopeException::missingId('array');
        }

        return new self(
            scopeType: $data['scope_type'],
            scopeId: (int) $data['scope_id']
        );
    }

    /**
     * Create from configuration key.
     *
     * @param  string  $configKey  Configuration key (e.g., 'sn-cms.scopeable')
     * @return self
     *
     * @throws InvalidScopeException
     */
    public static function fromConfig(string $configKey): self
    {
        $config = config($configKey);

        if (! is_array($config)) {
            throw InvalidScopeException::configNotFound($configKey);
        }

        if (! isset($config['scope_type']) || blank($config['scope_type'])) {
            throw InvalidScopeException::missingType($configKey);
        }

        if (! isset($config['scope_id'])) {
            throw InvalidScopeException::missingId($configKey);
        }

        return new self(
            scopeType: $config['scope_type'],
            scopeId: (int) $config['scope_id']
        );
    }

    /**
     * Convert to array format for database queries.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function toArray(): array
    {
        return [
            'scope_type' => $this->scopeType,
            'scope_id' => $this->scopeId,
        ];
    }

    /**
     * Validate the scope context.
     *
     * @throws InvalidScopeException
     */
    public function validate(): void
    {
        if (blank($this->scopeType)) {
            throw InvalidScopeException::invalidConfiguration('instance', 'scope_type cannot be empty');
        }

        if ($this->scopeId < 0) {
            throw InvalidScopeException::invalidConfiguration('instance', 'scope_id cannot be negative');
        }
    }

    /**
     * Check if this is a global scope (scope_id = 0).
     */
    public function isGlobal(): bool
    {
        return $this->scopeId === 0;
    }

    /**
     * Get a string representation.
     */
    public function toString(): string
    {
        return "{$this->scopeType}:{$this->scopeId}";
    }

    /**
     * Check equality with another scope context.
     */
    public function equals(ScopeableContext $other): bool
    {
        return $this->scopeType === $other->scopeType
            && $this->scopeId === $other->scopeId;
    }
}
