<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Features\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use RuntimeException;
use Wsmallnews\Support\Commands\PackageInstallCommand;

/**
 * 安装编排器：依赖图以 composer 为唯一事实源。
 *
 * 从目标包出发，读 vendor 下各 wsmallnews 包 composer.json 的 require，
 * 递归展开 wsmallnews/* 依赖并按拓扑序排列（每包只出现一次）。
 * 只有存在对应安装命令（{sn-xxx}:install，经 Artisan 校验）的包才纳入计划——
 * 无安装命令的包（如 filament-nestedset）自动跳过。
 *
 * 计划不含目标包自身（自身由当前命令直接处理），新增包零配置。
 */
class InstallPlanner
{
    /**
     * 生成依赖安装计划（拓扑序，不含 root）
     *
     * @param  string  $rootModule  目标模块 id（如 sn-cms）
     * @return Collection<int, array{name: string, command: string}> name = composer 包名
     */
    public static function plan(string $rootModule): Collection
    {
        $rootComposer = self::composerNameOf($rootModule);

        $visited = [];
        $order = [];

        $visit = function (string $composerName) use (&$visit, &$visited, &$order): void {
            if (isset($visited[$composerName])) {
                return;
            }
            $visited[$composerName] = true;

            foreach (self::dependenciesOf($composerName) as $dependency) {
                $visit($dependency);
            }

            $order[] = $composerName;
        };

        foreach (self::dependenciesOf($rootComposer) as $dependency) {
            $visit($dependency);
        }

        return collect($order)
            ->filter(fn (string $composerName) => filled(self::installCommandOf($composerName)))
            ->map(fn (string $composerName) => [
                'name' => $composerName,
                'command' => self::installCommandOf($composerName),
            ])
            ->values();
    }

    /**
     * 模块 id → composer 包名（sn-cms → wsmallnews/cms）
     */
    protected static function composerNameOf(string $module): string
    {
        return 'wsmallnews/' . Str::after($module, 'sn-');
    }

    /**
     * composer 包名 → 安装命令名（wsmallnews/cms → sn-cms:install）；
     * 仅纳入 PackageInstallCommand 基类的子类（支持 --as-dependency 依赖模式）——
     * 用原生 spatie hasInstallCommand 的包（如 filament-nestedset，独立工具包不依赖 support）
     * 返回 null 自动跳过，由用户按需单独执行其原生命令
     */
    protected static function installCommandOf(string $composerName): ?string
    {
        $command = 'sn-' . Str::after($composerName, 'wsmallnews/') . ':install';

        $instance = Artisan::all()[$command] ?? null;

        return $instance instanceof PackageInstallCommand ? $command : null;
    }

    /**
     * 包的直接 wsmallnews 依赖（读 vendor 下该包的 composer.json）
     *
     * @return array<int, string>
     */
    protected static function dependenciesOf(string $composerName): array
    {
        $manifest = base_path('vendor/' . $composerName . '/composer.json');

        if (! is_file($manifest)) {
            throw new RuntimeException("Package [{$composerName}] is not installed under vendor/: please run composer install first.");
        }

        $requires = json_decode((string) file_get_contents($manifest), true)['require'] ?? [];

        return collect(array_keys($requires))
            ->filter(fn (string $dependency) => str_starts_with($dependency, 'wsmallnews/'))
            ->values()
            ->all();
    }
}
