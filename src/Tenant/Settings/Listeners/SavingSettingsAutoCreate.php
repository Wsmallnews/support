<?php

namespace Wsmallnews\Support\Tenant\Settings\Listeners;

use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Support\Crypto;

class SavingSettingsAutoCreate
{
    /** Create the event listener. */
    public function __construct()
    {
        //
    }

    /** Handle the event. */
    public function handle(SavingSettings $event): void
    {
        $settings = $event->settings;           // Settings with the newest values, as raw input
        $properties = $event->properties;           // Newest property values, as raw input
        $originalValues = $event->originalValues;   // Values before the edit, decoded from the database
        $config = $settings->config;                // config is private but exposed through __get

        // Initialisation skips the isLocked check, which only applies to later saves.
        $notRejectedProperties = $properties;

        $changedProperties = $notRejectedProperties
            ->map(function ($payload, string $name) use ($config) {
                if ($cast = $config->getCast($name)) {
                    $payload = $cast->set($payload);
                }

                if ($config->isEncrypted($name)) {
                    $payload = Crypto::encrypt($payload);
                }

                return $payload;
            });

        // Save each setting with the default declared on the settings class
        $changedProperties->each(function ($payload, string $name) use ($config) {
            // Skip settings that already exist
            if ($config->getRepository()->checkIfPropertyExists(
                $config->getGroup(),
                $name
            )) {
                return;
            }

            // Create the initial settings
            $config->getRepository()->createProperty(
                $config->getGroup(),
                $name,
                $payload
            );
        });

        // Reset properties marked defaultValueLoaded, or later reads still raise MissingSettings
        $config->resetDefaultValueLoadedProperties();
    }
}
