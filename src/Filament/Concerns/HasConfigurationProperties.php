<?php

namespace Wsmallnews\Support\Filament\Concerns;

use BackedEnum;
use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use UnitEnum;

trait HasConfigurationProperties
{
    use EvaluatesClosures;

    protected string | UnitEnum | Closure | null $navigationGroup = null;

    protected string | Closure | null $navigationLabel = null;

    protected string | BackedEnum | Closure | null $navigationIcon = null;

    protected string | BackedEnum | Closure | null $activeNavigationIcon = null;

    protected int | Closure | null $navigationSort = null;

    protected string | Closure | null $navigationParentItem = null;

    protected bool | Closure | null $shouldRegisterNavigation = null;

    protected string | int | Closure | null $navigationBadge = null;

    protected string | array | Closure | null $navigationBadgeColor = null;

    protected string | Closure | null $navigationBadgeTooltip = null;

    protected string | Closure | null $subNavigationPosition = null;

    protected string | Closure | null $modelLabel = null;

    protected string | Closure | null $pluralModelLabel = null;

    protected string | Closure | null $recordTitleAttribute = null;

    protected bool | Closure | null $hasTitleCaseModelLabel = null;

    protected bool | Closure | null $isScopedToTenant = null;

    protected string | Closure | null $tenantOwnershipRelationshipName = null;

    protected string | Closure | null $tenantRelationshipName = null;

    protected string | Closure | null $parentResource = null;

    protected bool | Closure | null $isGloballySearchable = null;

    protected int | Closure | null $globalSearchResultsLimit = null;

    protected bool | Closure | null $isGlobalSearchForcedCaseInsensitive = null;

    protected bool | Closure | null $shouldSplitGlobalSearchTerms = null;

    protected string | Closure | null $scopeType = null;

    protected int | Closure | null $scopeId = null;

    protected array $customProperties = [];

    public function navigationGroup(string | UnitEnum | Closure | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string | UnitEnum | Closure | null
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function navigationLabel(string | Closure | null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->evaluate($this->navigationLabel);
    }

    public function navigationIcon(string | BackedEnum | Closure | null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string | BackedEnum | null
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function activeNavigationIcon(string | BackedEnum | Closure | null $icon): static
    {
        $this->activeNavigationIcon = $icon;

        return $this;
    }

    public function getActiveNavigationIcon(): string | BackedEnum | null
    {
        return $this->evaluate($this->activeNavigationIcon);
    }

    public function navigationSort(int | Closure | null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    public function navigationParentItem(string | Closure | null $item): static
    {
        $this->navigationParentItem = $item;

        return $this;
    }

    public function getNavigationParentItem(): ?string
    {
        return $this->evaluate($this->navigationParentItem);
    }

    public function shouldRegisterNavigation(bool | Closure | null $condition = true): static
    {
        $this->shouldRegisterNavigation = $condition;

        return $this;
    }

    public function getShouldRegisterNavigation(): ?bool
    {
        return $this->evaluate($this->shouldRegisterNavigation);
    }

    public function navigationBadge(string | int | Closure | null $badge): static
    {
        $this->navigationBadge = $badge;

        return $this;
    }

    public function getNavigationBadge(): string | int | null
    {
        return $this->evaluate($this->navigationBadge);
    }

    public function navigationBadgeColor(string | array | Closure | null $color): static
    {
        $this->navigationBadgeColor = $color;

        return $this;
    }

    public function getNavigationBadgeColor(): string | array | null
    {
        return $this->evaluate($this->navigationBadgeColor);
    }

    public function navigationBadgeTooltip(string | Closure | null $tooltip): static
    {
        $this->navigationBadgeTooltip = $tooltip;

        return $this;
    }

    public function getNavigationBadgeTooltip(): ?string
    {
        return $this->evaluate($this->navigationBadgeTooltip);
    }

    public function subNavigationPosition(string | Closure | null $position): static
    {
        $this->subNavigationPosition = $position;

        return $this;
    }

    public function getSubNavigationPosition(): ?string
    {
        return $this->evaluate($this->subNavigationPosition);
    }

    // ========================================================================
    // Label setters & getters
    // ========================================================================

    public function modelLabel(string | Closure | null $label): static
    {
        $this->modelLabel = $label;

        return $this;
    }

    public function getModelLabel(): ?string
    {
        return $this->evaluate($this->modelLabel);
    }

    public function pluralModelLabel(string | Closure | null $label): static
    {
        $this->pluralModelLabel = $label;

        return $this;
    }

    public function getPluralModelLabel(): ?string
    {
        return $this->evaluate($this->pluralModelLabel);
    }

    public function recordTitleAttribute(string | Closure | null $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->evaluate($this->recordTitleAttribute);
    }

    public function hasTitleCaseModelLabel(bool | Closure | null $condition = true): static
    {
        $this->hasTitleCaseModelLabel = $condition;

        return $this;
    }

    public function getHasTitleCaseModelLabel(): ?bool
    {
        return $this->evaluate($this->hasTitleCaseModelLabel);
    }

    // ========================================================================
    // Tenant setters & getters
    // ========================================================================

    public function isScopedToTenant(bool | Closure | null $condition = true): static
    {
        $this->isScopedToTenant = $condition;

        return $this;
    }

    public function getIsScopedToTenant(): ?bool
    {
        return $this->evaluate($this->isScopedToTenant);
    }

    public function tenantOwnershipRelationshipName(string | Closure | null $name): static
    {
        $this->tenantOwnershipRelationshipName = $name;

        return $this;
    }

    public function getTenantOwnershipRelationshipName(): ?string
    {
        return $this->evaluate($this->tenantOwnershipRelationshipName);
    }

    public function tenantRelationshipName(string | Closure | null $name): static
    {
        $this->tenantRelationshipName = $name;

        return $this;
    }

    public function getTenantRelationshipName(): ?string
    {
        return $this->evaluate($this->tenantRelationshipName);
    }

    // ========================================================================
    // Parent resource setter & getter
    // ========================================================================

    public function parentResource(string | Closure | null $resource): static
    {
        $this->parentResource = $resource;

        return $this;
    }

    public function getParentResource(): ?string
    {
        return $this->evaluate($this->parentResource);
    }

    // ========================================================================
    // Global Search setters & getters
    // ========================================================================

    public function isGloballySearchable(bool | Closure | null $condition = true): static
    {
        $this->isGloballySearchable = $condition;

        return $this;
    }

    public function getIsGloballySearchable(): ?bool
    {
        return $this->evaluate($this->isGloballySearchable);
    }

    public function globalSearchResultsLimit(int | Closure | null $limit): static
    {
        $this->globalSearchResultsLimit = $limit;

        return $this;
    }

    public function getGlobalSearchResultsLimit(): ?int
    {
        return $this->evaluate($this->globalSearchResultsLimit);
    }

    public function isGlobalSearchForcedCaseInsensitive(bool | Closure | null $condition = true): static
    {
        $this->isGlobalSearchForcedCaseInsensitive = $condition;

        return $this;
    }

    public function getIsGlobalSearchForcedCaseInsensitive(): ?bool
    {
        return $this->evaluate($this->isGlobalSearchForcedCaseInsensitive);
    }

    public function shouldSplitGlobalSearchTerms(bool | Closure | null $condition = true): static
    {
        $this->shouldSplitGlobalSearchTerms = $condition;

        return $this;
    }

    public function getShouldSplitGlobalSearchTerms(): ?bool
    {
        return $this->evaluate($this->shouldSplitGlobalSearchTerms);
    }

    // ========================================================================
    // Scope setters & getters
    // ========================================================================

    public function scopeType(string | Closure | null $scopeType): static
    {
        $this->scopeType = $scopeType;

        return $this;
    }

    public function getScopeType(): ?string
    {
        return $this->evaluate($this->scopeType);
    }

    public function scopeId(int | Closure | null $scopeId): static
    {
        $this->scopeId = $scopeId;

        return $this;
    }

    public function getScopeId(): ?int
    {
        return $this->evaluate($this->scopeId);
    }

    // ========================================================================
    // Custom properties
    // ========================================================================

    public function customProperties(array $properties): static
    {
        $this->customProperties = array_merge($this->customProperties, $properties);

        return $this;
    }

    public function getCustomProperties(): array
    {
        return $this->customProperties;
    }

    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return $this->customProperties[$key] ?? $default;
    }
}
