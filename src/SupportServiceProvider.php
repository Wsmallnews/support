<?php

namespace Wsmallnews\Support;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Livewire\Livewire;
use RalphJSmit\Livewire\Urls\Middleware\LivewireUrlsMiddleware;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelSettings\Events\SavingSettings;
use Wsmallnews\Support\Commands\SupportInstallCommand;
use Wsmallnews\Support\Http\Middleware\IdentifyTenant;
use Wsmallnews\Support\Support\BuilderMacros;
use Wsmallnews\Support\Support\Utils as SupportUtils;
use Wsmallnews\Support\Tenant\Settings\Listeners\SavingSettingsAutoCreate;

class SupportServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sn-support';

    public static string $viewNamespace = 'sn-support';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasTranslations()
            ->hasViews(static::$viewNamespace);
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // 注册模型别名
        Relation::enforceMorphMap([
            'sn_sms_log' => SupportUtils::getSmsLogModel(),
            'sn_content' => SupportUtils::getContentModel(),
            'activity' => ActivitylogConfig::activityModel(),
        ]);

        // 多租户时注册自动创建设置监听器 (不需要迁移默认配置)
        if (SupportUtils::isTenancyEnabled()) {
            Event::listen(
                SavingSettings::class,
                SavingSettingsAutoCreate::class,
            );
        }

        // 给 builder 注册自定义方法
        BuilderMacros::register();

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/support/{$file->getFilename()}"),
                ], 'support-stubs');
            }
        }

        // 全局注册 租户校验中间件(前端请求的)
        Livewire::addPersistentMiddleware([
            IdentifyTenant::class,
            // 记录路由历史
            LivewireUrlsMiddleware::class,
        ]);

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

        // \Filament\Tables\Table::$defaultCurrency = 'CNY';
        // \Filament\Tables\Table::$defaultDateDisplayFormat = 'M j, Y';
        // \Filament\Tables\Table::$defaultDateTimeDisplayFormat = 'M j, Y H:i:s';
        // \Filament\Tables\Table::$defaultNumberLocale = null;
        // \Filament\Tables\Table::$defaultTimeDisplayFormat = 'H:i:s';

        // \Filament\Infolists\Infolist::$defaultCurrency = 'CNY';
        // \Filament\Infolists\Infolist::$defaultDateDisplayFormat = 'M j, Y';
        // \Filament\Infolists\Infolist::$defaultDateTimeDisplayFormat = 'M j, Y H:i:s';
        // \Filament\Infolists\Infolist::$defaultNumberLocale = null;
        // \Filament\Infolists\Infolist::$defaultTimeDisplayFormat = 'H:i:s';

        // // laravel number 类库
        // \Illuminate\Support\Number::useLocale(config('app.locale'));
        // \Illuminate\Support\Number::useCurrency('CNY');

        // // Cknow\Money
        // \Cknow\Money\Money::setDefaultCurrency('CNY');

        // 定义图片变体
        // ImageManipulator::defineVariant(
        //     'thumbnail',
        //     ImageManipulation::make(function (Image $image, Media $originalMedia) {
        //         $image->scaleDown(200, 200);
        //     })
        // );

        // ImageManipulator::defineVariant(
        //     'medium',
        //     ImageManipulation::make(function (Image $image, Media $originalMedia) {
        //         $image->scaleDown(500, 500);
        //     })
        // );

        // ImageManipulator::defineVariant(
        //     'large',
        //     ImageManipulation::make(function (Image $image, Media $originalMedia) {
        //         $image->scaleDown(800, 800);
        //     })
        // );
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
            Js::make('sn-support-index', __DIR__ . '/../resources/dist/index.js'),

            // AlpineComponent::make('forms-arrange', __DIR__ . '/../resources/dist/forms/arrange.js'),
            AlpineComponent::make('sn-support-components-lightbox', __DIR__ . '/../resources/dist/components/lightbox.js'),
            AlpineComponent::make('sn-support-components-swiper', __DIR__ . '/../resources/dist/components/swiper.js'),
            // AlpineComponent::make('components-file-upload', __DIR__ . '/../resources/dist/components/file-upload.js'),
            Css::make('sn-support-components-lightbox', __DIR__ . '/../resources/dist/components/lightbox.css')->loadedOnRequest(),
            Css::make('sn-support-components-swiper', __DIR__ . '/../resources/dist/components/swiper.css')->loadedOnRequest(),
            // Css::make('support-styles', __DIR__ . '/../resources/dist/support.css'),

            // AlpineComponent::make('support', __DIR__ . '/../resources/dist/components/support.js'),
            // Js::make('support-scripts', __DIR__ . '/../resources/dist/support.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            SupportInstallCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
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
            // 'create_sn_sms_logs_table',
            'create_sn_team_settings_table',
            'create_sn_contents_table',
            'add_teams_fields_to_activity_log_table',
        ];
    }
}
