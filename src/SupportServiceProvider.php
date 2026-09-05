<?php

namespace Wsmallnews\Support;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RalphJSmit\Livewire\Urls\Middleware\LivewireUrlsMiddleware;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Wsmallnews\Support\Commands\RunScheduledTasksCommand;
use Wsmallnews\Support\Commands\SupportInstallCommand;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Seo\Seo;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;
use Wsmallnews\Support\Helpers\ScheduleHelper;
use Wsmallnews\Support\Http\Middleware\IdentifyTenant;
use Wsmallnews\Support\Http\Middleware\InitializeSeo;
use Wsmallnews\Support\Settings\Listeners\LogSettingsActivity;
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

        // 包级路由文件（当前承载站点级 SEO 端点；是否注册具体路由由路由文件内的配置决定）
        $package->hasRoutes($this->getRoutes());
    }

    public function packageRegistered(): void
    {
        // 注册定时调度任务注册器
        $this->app->singleton(ScheduledTaskRegistry::class, function (): ScheduledTaskRegistry {
            return new ScheduledTaskRegistry;
        });

        // 注册通用全局搜索注册器
        $this->app->singleton(SearchRegistry::class, function (): SearchRegistry {
            return new SearchRegistry;
        });

        // 注册 SEO 渲染器（页面级链式声明 + 消费方注册的站点默认值 provider）
        $this->app->singleton(Seo::class, function (): Seo {
            return new Seo;
        });

        // 注册 sitemap 聚合注册表（各扩展包注册 URL 来源，站点路由聚合输出）
        $this->app->singleton(SitemapRegistry::class, function (): SitemapRegistry {
            return new SitemapRegistry;
        });

        // seo-init:模块名 —— 首屏初始化页面 SEO 上下文（普通中间件，勿加入 Livewire 持久化清单）
        $this->app['router']->aliasMiddleware('seo-init', InitializeSeo::class);

        // sn-* 设计令牌运行时覆盖（config sn-support.theme），在 layout 中以 @snTheme 输出
        Blade::directive('snTheme', function (): string {
            return '<?php echo \Wsmallnews\Support\Support\Theme::styles(); ?>';
        });

        // 页面 SEO 标签（title/meta/OG/canonical/JSON-LD），layout <head> 中以 @snSeo 输出
        Blade::directive('snSeo', function (): string {
            return '<?php echo app(\Wsmallnews\Support\Features\Seo\Seo::class)->render(); ?>';
        });

        // 统计代码（模块 analytics_code），layout </body> 前以 @snSeoAnalytics 输出
        Blade::directive('snSeoAnalytics', function (): string {
            return '<?php echo app(\Wsmallnews\Support\Features\Seo\Seo::class)->renderAnalytics(); ?>';
        });
    }

    public function packageBooted(): void
    {
        // 注册模型别名
        Relation::enforceMorphMap([
            'sn_sms_log' => SupportUtils::getSmsLogModel(),
            'sn_content' => SupportUtils::getContentModel(),
            'sn_scheduled_task' => SupportUtils::getScheduledTaskModel(),
            'activity' => ActivitylogConfig::activityModel(),
            'settings' => SettingsProperty::class,
        ]);

        // 自动记录设置修改日志
        Event::listen(
            SavingSettings::class,
            LogSettingsActivity::class,
        );

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

        // 注册 livewire 命名空间（自动发现 src/Livewire/ 下的组件，如 sn-support::components.search）
        Livewire::addNamespace(
            namespace: 'sn-support',
            classNamespace: 'Wsmallnews\Support\Livewire'
        );

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

        // 注册定时调度任务（频率等配置从 sn-support.scheduler 读取）
        if (SupportUtils::getSchedulerConfig('enabled', true)) {
            $schedulerConfig = [
                'frequency' => SupportUtils::getSchedulerConfig('frequency', 'everyMinute'),
                'without_overlapping' => SupportUtils::getSchedulerConfig('without_overlapping', true),
                'overlapping_expire_minutes' => SupportUtils::getSchedulerConfig('overlapping_expire_minutes', 5),
            ];

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) use ($schedulerConfig) {
                $task = $schedule->command('sn-support:run-scheduled-tasks');
                ScheduleHelper::configure($task, $schedulerConfig);
            });
        }
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
            RunScheduledTasksCommand::class,
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
        return ['web'];
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
            'create_sn_scheduled_tasks_table',
            'add_teams_fields_to_activity_log_table',
        ];
    }
}
