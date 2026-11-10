<?php

namespace Wsmallnews\Support\Models;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Wsmallnews\Support\Enums\TeamStatus;

/**
 * Base tenant model.
 *
 * Extend it in the host app when the tenant needs extra relations or behaviour,
 * then point sn-support.tenant_model at the subclass:
 *
 *   class Team extends \Wsmallnews\Support\Models\Team
 *   {
 *       public function users(): BelongsToMany { ... }
 *   }
 */
class Team extends SupportModel implements HasAvatar, HasName
{
    protected $table = 'teams';

    protected $attributes = [
        'status' => 'enabled',
    ];

    protected $casts = [
        'options' => 'array',
        'status' => TeamStatus::class,
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? files_url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
