<?php

namespace Wsmallnews\Support\Tenant\Settings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;
use Wsmallnews\Support\Features\Site\SiteContext;

/**
 * Site-scoped settings storage: rows are partitioned by the current site's
 * scope pair and the current tenant. A null tenant reads/writes the platform
 * rows (team_id IS NULL); a missing site context fails closed — settings
 * always belong to one site, never to an implicit bucket.
 */
class DatabaseSettingsRepository implements SettingsRepository
{
    /** @var class-string<Model> */
    protected string $propertyModel;

    protected ?string $connection;

    protected ?string $table;

    public function __construct(array $config)
    {
        $this->propertyModel = $config['model'] ?? SettingsProperty::class;
        $this->connection = $config['connection'] ?? null;
        $this->table = $config['table'] ?? null;
    }

    public function getPropertiesInGroup(string $group): array
    {
        return $this->getBuilder()
            ->where('group', $group)
            ->get(['name', 'payload'])
            ->mapWithKeys(function (object $object) {
                return [$object->name => $this->decode($object->payload, true)];
            })
            ->toArray();
    }

    public function checkIfPropertyExists(string $group, string $name): bool
    {
        return $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name)
            ->exists();
    }

    public function getPropertyPayload(string $group, string $name)
    {
        $setting = $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name)
            ->first('payload')
            ->toArray();

        return $this->decode($setting['payload']);
    }

    public function createProperty(string $group, string $name, $payload, bool $locked = false): void
    {
        $this->getBuilder()->create([
            ...$this->rowAttributes($group, $name),
            'payload' => $this->encode($payload),
            'locked' => $locked,
        ]);
    }

    public function updatePropertiesPayload(string $group, array $properties): void
    {
        // Rows may carry a null team_id, which no unique index can deduplicate
        // — upsert per property instead of one SQL upsert, so a save never
        // inserts duplicate rows.
        foreach ($properties as $name => $payload) {
            $attributes = $this->rowAttributes($group, $name);

            $existing = $this->getBuilder()->where($attributes)->first();

            if ($existing === null) {
                $this->getBuilder()->create([
                    ...$attributes,
                    'payload' => $this->encode($payload),
                ]);

                continue;
            }

            $this->getBuilder()
                ->where($attributes)
                ->update(['payload' => $this->encode($payload)]);
        }
    }

    public function deleteProperty(string $group, string $name): void
    {
        $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name)
            ->delete();
    }

    public function lockProperties(string $group, array $properties): void
    {
        $this->getBuilder()
            ->where('group', $group)
            ->whereIn('name', $properties)
            ->update(['locked' => true]);
    }

    public function unlockProperties(string $group, array $properties): void
    {
        $this->getBuilder()
            ->where('group', $group)
            ->whereIn('name', $properties)
            ->update(['locked' => false]);
    }

    public function getLockedProperties(string $group): array
    {
        return $this->getBuilder()
            ->where('group', $group)
            ->where('locked', true)
            ->pluck('name')
            ->toArray();
    }

    public function getBuilder(): Builder
    {
        $model = new $this->propertyModel;

        if ($this->connection) {
            $model->setConnection($this->connection);
        }

        if ($this->table) {
            $model->setTable($this->table);
        }

        $scopeable = $this->getScopeable();

        $query = $model->newQuery()
            ->where('scope_type', $scopeable['scope_type'])
            ->where('scope_id', $scopeable['scope_id']);

        $team_id = $this->getTeamId();

        return $team_id === null
            ? $query->whereNull('team_id')
            : $query->where('team_id', $team_id);
    }

    /** Attributes that identify one settings row in the current context. */
    protected function rowAttributes(string $group, string $name): array
    {
        return [
            'team_id' => $this->getTeamId(),
            'scope_type' => $this->getScopeable()['scope_type'],
            'scope_id' => $this->getScopeable()['scope_id'],
            'group' => $group,
            'name' => $name,
        ];
    }

    /** Site scope of the current context; a missing site context fails closed. */
    protected function getScopeable(): array
    {
        return app(SiteContext::class)->scopeableOrDefault();
    }

    /** Current tenant id; null means the platform rows. */
    protected function getTeamId(): int | string | null
    {
        return current_tenant()?->getKey();
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    protected function encode($value)
    {
        $encoder = config('settings.encoder') ?? fn ($value) => json_encode($value);

        return $encoder($value);
    }

    /**
     * @return mixed
     */
    protected function decode(string $payload, bool $associative = false)
    {
        $decoder = config('settings.decoder') ?? fn ($payload, $associative) => json_decode($payload, $associative);

        return $decoder($payload, $associative);
    }
}
