<?php

namespace Wsmallnews\Support;

use Cknow\Money\Money as CknowMoney;
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
use Wsmallnews\Support\Features\Composition\CompositionRegistry;
use Wsmallnews\Support\Features\Feed\FeedRegistry;
use Wsmallnews\Support\Features\Modules\Module;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Seo\Seo;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Features\Site\SiteRouteRegistry;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;
use Wsmallnews\Support\Helpers\ScheduleHelper;
use Wsmallnews\Support\Http\Middleware\ResolveSeo;
use Wsmallnews\Support\Http\Middleware\ResolveSiteContext;
use Wsmallnews\Support\Settings\Listeners\LogSettingsActivity;
use Wsmallnews\Support\Support\BuilderMacros;
use Wsmallnews\Support\Support\Utils as SupportUtils;
use Wsmallnews\Support\Tenant\Settings\Listeners\SavingSettingsAutoCreate;
use Wsmallnews\Support\Tenant\Settings\SettingsRepositoryGuard;

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

        // Package routes; the route file itself decides which endpoints to register
        $package->hasRoutes($this->getRoutes());
    }

    public function packageRegistered(): void
    {
        ModuleRegistry::register(new Module(
            id: static::$name,
            namespace: 'Wsmallnews\\Support',
            plugin: null,
        ));

        // Composition registry, keyed by module id
        $this->app->singleton(CompositionRegistry::class, function (): CompositionRegistry {
            return new CompositionRegistry;
        });

        // Scheduled task registry
        $this->app->singleton(ScheduledTaskRegistry::class, function (): ScheduledTaskRegistry {
            return new ScheduledTaskRegistry;
        });

        // Global search registry
        $this->app->singleton(SearchRegistry::class, function (): SearchRegistry {
            return new SearchRegistry;
        });

        // SEO renderer: page declarations plus module defaults
        $this->app->singleton(Seo::class, function (): Seo {
            return new Seo;
        });

        // Sitemap registry
        $this->app->singleton(SitemapRegistry::class, function (): SitemapRegistry {
            return new SitemapRegistry;
        });

        // RSS feed registry
        $this->app->singleton(FeedRegistry::class, function (): FeedRegistry {
            return new FeedRegistry;
        });

        // Site registry (filled by Filament plugins while panels are constructed)
        $this->app->singleton(SiteRegistry::class, function (): SiteRegistry {
            return new SiteRegistry;
        });

        // Per-site route extensions, keyed by module + module-defined slot
        $this->app->singleton(SiteRouteRegistry::class, function (): SiteRouteRegistry {
            return new SiteRouteRegistry;
        });

        // Request-scoped site context (reset per request, Octane-safe)
        $this->app->scoped(SiteContext::class, function (): SiteContext {
            return new SiteContext;
        });

        // sn-resolve-seo:{module} rebuilds the page SEO context; a normal, non-persistent middleware
        $this->app['router']->aliasMiddleware('sn-resolve-seo', ResolveSeo::class);

        // Resolve the frontend site (and its tenant, when the site mode calls for one) into SiteContext
        $this->app['router']->aliasMiddleware('sn-resolve-site-context', ResolveSiteContext::class);

        // Runtime token overrides (config sn-support.theme), emitted with @snTheme
        Blade::directive('snTheme', function (): string {
            return '<?php echo \Wsmallnews\Support\Support\Theme::styles(); ?>';
        });

        // SEO tags for <head>, emitted with @snSeo
        Blade::directive('snSeo', function (): string {
            return '<?php echo app(\Wsmallnews\Support\Features\Seo\Seo::class)->render(); ?>';
        });

        // Per-site autodiscovery links, emitted with @snFeeds inside a site layout.
        // A legacy @snFeeds('sn-cms') argument is accepted and ignored: the module is
        // derived from the site.
        Blade::directive('snFeeds', function (string $expression): string {
            return "<?php \$__snSite = app(\Wsmallnews\Support\Features\Site\SiteContext::class)->site();"
                . " echo \$__snSite === null ? '' : app(\Wsmallnews\Support\Features\Feed\FeedRegistry::class)->autodiscoveryTags(\$__snSite)->toHtml(); ?>";
        });

        // Analytics snippet, emitted with @snSeoAnalytics before </body>
        Blade::directive('snSeoAnalytics', function (): string {
            return '<?php echo app(\Wsmallnews\Support\Features\Seo\Seo::class)->renderAnalytics(); ?>';
        });
    }

    public function packageBooted(): void
    {
        // Plugin settings resolve rows through support's repositories; a
        // missing one only surfaces as a bare factory error later — fail at
        // boot instead.
        SettingsRepositoryGuard::assertRegistered();

        // Register morph aliases
        Relation::enforceMorphMap([
            'sn_sms_log' => SupportUtils::getSmsLogModel(),
            'sn_content' => SupportUtils::getContentModel(),
            'sn_scheduled_task' => SupportUtils::getScheduledTaskModel(),
            'sn_composition' => SupportUtils::getCompositionModel(),
            'sn_page' => SupportUtils::getPageModel(),
            'activity' => ActivitylogConfig::activityModel(),
            'settings' => SettingsProperty::class,
        ]);

        // Log setting changes
        Event::listen(
            SavingSettings::class,
            LogSettingsActivity::class,
        );

        // Create missing settings rows automatically (platform and tenant repositories alike)
        Event::listen(
            SavingSettings::class,
            SavingSettingsAutoCreate::class,
        );

        // Register the builder macros
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

        // Global tenant middleware for frontend requests
        Livewire::addPersistentMiddleware([
            ResolveSiteContext::class,
            // Record route history
            LivewireUrlsMiddleware::class,
        ]);

        // Register the Livewire namespace, so src/Livewire is auto-discovered
        Livewire::addNamespace(
            namespace: 'sn-support',
            classNamespace: 'Wsmallnews\Support\Livewire'
        );

        // Align number and currency defaults: app.currency, then sn-support.currency, then CNY
        $defaultCurrency = sn_money()->defaultCurrency();
        Number::useLocale(config('app.locale'));
        Number::useCurrency($defaultCurrency);
        CknowMoney::setDefaultCurrency($defaultCurrency);

        Number::macro('symbol', function (?string $in = null, ?string $locale = null) {
            $in = $in ?: Number::defaultCurrency();
            $locale = $locale ?? config('app.locale');

            $formatCurrency = Number::currency(0, $in, $locale);

            $symbol = Str::replaceMatches(
                pattern: '/(?<=\W)\d+\.?\d*/u',
                replace: '',
                subject: $formatCurrency
            );

            return $symbol;
        });

        // Register scheduled tasks; the frequency comes from sn-support.scheduler
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

            AlpineComponent::make('sn-support-components-lightbox', __DIR__ . '/../resources/dist/components/lightbox.js'),
            AlpineComponent::make('sn-support-components-number-stepper', __DIR__ . '/../resources/dist/components/number-stepper.js'),
            AlpineComponent::make('sn-support-components-swiper', __DIR__ . '/../resources/dist/components/swiper.js'),
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
            'create_teams_table',
            'create_sn_site_settings_table',
            'create_sn_contents_table',
            'create_sn_scheduled_tasks_table',
            'create_sn_compositions_table',
            'create_sn_pages_table',
            'add_teams_fields_to_activity_log_table',
        ];
    }
}
