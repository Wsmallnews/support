<?php

namespace Wsmallnews\Support\Tenant\Settings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;

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
            'team_id' => $this->getTeamId(),
            'group' => $group,
            'name' => $name,
            'payload' => $this->encode($payload),
            'locked' => $locked,
        ]);
    }

    public function updatePropertiesPayload(string $group, array $properties): void
    {
        $propertiesInBatch = collect($properties)->map(function ($payload, $name) use ($group) {
            return [
                'team_id' => $this->getTeamId(),
                'group' => $group,
                'name' => $name,
                'payload' => $this->encode($payload),
            ];
        })->values()->toArray();

        $this->getBuilder()
            ->where('group', $group)
            ->upsert($propertiesInBatch, ['team_id', 'group', 'name'], ['payload']);
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

        // 获取租户 id
        $team_id = $this->getTeamId();

        return $model->newQuery()->where('team_id', $team_id);
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    protected function encode($value)
    {
        $encoder = config('settings.encoder') ?? fn($value) => json_encode($value);

        return $encoder($value);
    }

    /**
     * @return mixed
     */
    protected function decode(string $payload, bool $associative = false)
    {
        $decoder = config('settings.decoder') ?? fn($payload, $associative) => json_decode($payload, $associative);

        return $decoder($payload, $associative);
    }

    /**
     * 获取当前租户 id
     */
    protected function getTeamId(): int
    {
        $team_id = current_tenant()?->id;

        if (is_null($team_id)) {
            throw new \Exception('Team setting: Tenant not found.');
        }

        return $team_id;
    }
}
