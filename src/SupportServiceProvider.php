<?php

namespace Wsmallnews\Support;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RalphJSmit\Livewire\Urls\Middleware\LivewireUrlsMiddleware;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelSettings\Events\SavingSettings;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Http\Middleware\IdentifyTenant;
use Wsmallnews\Support\Support\Utils as SupportUtils;
use Wsmallnews\Support\Tenant\Settings\Listeners\SavingSettingsAutoCreate;
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

        Builder::macro('incrementJson', function ($jsonPath, $amount = 1, array $extra = []) {
            /** @var Builder $this */
            $fields = explode('->', $jsonPath);

            $field = $fields[0] ?? null;
            $subField = $fields[1] ?? null;
            if (blank($field) || blank($subField)) {
                throw new SupportException("json path format error: {$jsonPath}, for example, `counter->like_num`");
            }

            if (isset($extra['withupdate']) && $extra['withupdate']) {

                return $this->update([
                    $field => DB::raw("JSON_SET(
                        COALESCE({$field}, '{}'), '$.{$subField}', CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) + {$amount}
                    )"),
                ]);
            } else {
                Model::withoutTimestamps(
                    fn () => $this->update([
                        $field => DB::raw("JSON_SET(
                            COALESCE({$field}, '{}'), '$.{$subField}', CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) + {$amount}
                        )"),
                    ])
                );
            }
        });
        Builder::macro('decrementJson', function ($jsonPath, $amount = 1, array $extra = []) {
            /** @var Builder $this */
            $fields = explode('->', $jsonPath);

            $field = $fields[0] ?? null;
            $subField = $fields[1] ?? null;
            if (blank($field) || blank($subField)) {
                throw new SupportException("json path format error: {$jsonPath}, for example, `counter->like_num`");
            }

            if (isset($extra['withupdate']) && $extra['withupdate']) {
                return $this->update([
                    $field => DB::raw("JSON_SET(
                        COALESCE({$field}, '{}'), '$.{$subField}', GREATEST(
                            (CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) - {$amount})
                        , 0)
                    )"),
                ]);
            } else {
                Model::withoutTimestamps(
                    fn () => $this->update([
                        $field => DB::raw("JSON_SET(
                            COALESCE({$field}, '{}'), '$.{$subField}', GREATEST(
                                (CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) - {$amount})
                            , 0)
                        )"),
                    ])
                );
            }
        });

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

        // 全局注册 租户校验中间件(前端请求的)
        Livewire::addPersistentMiddleware([
            IdentifyTenant::class,
            // 记录路由历史
            LivewireUrlsMiddleware::class,
        ]);

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
            // '2025_01_20_113658_create_sn_sms_logs_table',
            // '2025_04_17_105524_add_scopeinfo_to_media_table',
            '2025_10_29_110527_create_sn_team_settings_table',
            '2025_11_01_213119_create_sn_contents_table',
            '2026_04_01_223809_create_activity_log_table',
            '2026_04_01_224322_add_teams_fields_to_activity_log_table',
        ];
    }
}
