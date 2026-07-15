<?php

namespace Wsmallnews\Support\Filament\Concerns;

trait RegistersConfigurable
{
    /**
     * @var array<\Filament\Resources\ResourceConfiguration>
     */
    protected array $resourceConfigurations = [];

    /**
     * @var array<\Filament\Pages\PageConfiguration>
     */
    protected array $pageConfigurations = [];

    /**
     * Accept pre-built ResourceConfiguration objects from PanelProvider.
     * 
     * @param array<\Filament\Resources\ResourceConfiguration> $configurations
     * 
     * @return $this
     */
    public function configurableResources(array $configurations): static
    {
        $this->resourceConfigurations = $configurations;

        return $this;
    }

    /**
     * Get the configurable resources.
     *
     * @return array
     */
    public function getConfigurableResources(): array 
    {
        return $this->resourceConfigurations;
    }


    /**
     * Accept pre-built PageConfiguration objects from PanelProvider.
     * 
     * @param array<\Filament\Pages\PageConfiguration> $configurations
     * 
     * @return $this
     */
    public function configurablePages(array $configurations): static
    {
        $this->pageConfigurations = $configurations;

        return $this;
    }

    /**
     * Get the configurable pages.
     *
     * @return array
     */
    public function getConfigurablePages(): array
    {
        return $this->pageConfigurations;
    }
}
