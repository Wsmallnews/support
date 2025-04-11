<?php

namespace Wsmallnews\Support;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wsmallnews\Support\Models\SmsLog;
use Wsmallnews\Support\Testing\TestsSupport;

class SupportServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sn-support';

    public static string $viewNamespace = 'sn-support';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('wsmallnews/support');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
            $package->runsMigrations();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // 注册模型别名
        Relation::enforceMorphMap([
            'sn_sms_log' => SmsLog::class,
        ]);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/support/{$file->getFilename()}"),
                ], 'support-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsSupport);

        // 暂时先放开
        Number::macro('symbol', function (string $in = 'USD', ?string $locale = null) {
            $locale = $locale ?? config('app.locale');

            $formatCurrency = Number::currency(0, $in, $locale);

            $symbol = Str::replaceMatches(
                pattern: '/(?<=\W)\d+\.?\d*/u',
                replace: '',
                subject: $formatCurrency
            );

            return $symbol;
        });

        \Filament\Tables\Table::$defaultCurrency = 'CNY';
        \Filament\Tables\Table::$defaultDateDisplayFormat = 'M j, Y';
        \Filament\Tables\Table::$defaultDateTimeDisplayFormat = 'M j, Y H:i:s';
        \Filament\Tables\Table::$defaultNumberLocale = null;
        \Filament\Tables\Table::$defaultTimeDisplayFormat = 'H:i:s';

        \Filament\Infolists\Infolist::$defaultCurrency = 'CNY';
        \Filament\Infolists\Infolist::$defaultDateDisplayFormat = 'M j, Y';
        \Filament\Infolists\Infolist::$defaultDateTimeDisplayFormat = 'M j, Y H:i:s';
        \Filament\Infolists\Infolist::$defaultNumberLocale = null;
        \Filament\Infolists\Infolist::$defaultTimeDisplayFormat = 'H:i:s';

        // laravel number 类库
        \Illuminate\Support\Number::useLocale(config('app.locale'));
        \Illuminate\Support\Number::useCurrency('CNY');

        // Cknow\Money
        \Cknow\Money\Money::setDefaultCurrency('CNY');
    }

    protected function getAssetPackageName(): ?string
    {
        return 'wsmallnews/support';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('forms-arrange', __DIR__ . '/../resources/dist/forms/arrange.js'),
            AlpineComponent::make('components-swiper', __DIR__ . '/../resources/dist/components/swiper.js'),
            AlpineComponent::make('components-file-upload', __DIR__ . '/../resources/dist/components/file-upload.js'),
            Css::make('components-swiper', __DIR__ . '/../resources/dist/components/swiper.css')->loadedOnRequest(),
            Css::make('support-styles', __DIR__ . '/../resources/dist/support.css'),

            // AlpineComponent::make('support', __DIR__ . '/../resources/dist/components/support.js'),
            // Js::make('support-scripts', __DIR__ . '/../resources/dist/support.js'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            '2025_01_20_113658_create_sn_sms_logs_table',
        ];
    }
}
