<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static static register(string $module, array $typeInfo)
 * @method static static registers(string $module, array $typeInfos)
 * @method static Collection getModules()
 * @method static Collection getTypes(string $module)
 * @method static array|null getType(string $module, string $type)
 * @method static array getTypesOptions(string $module)
 * @method static bool hasTypeForms(string $module, string $type, array $arguments = [])
 * @method static array getTypeForms(string $module, string $type, array $arguments = [])
 *
 * @see \Wsmallnews\Support\Features\Composition\CompositionRegistry
 */
class CompositionRegistry extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Wsmallnews\Support\Features\Composition\CompositionRegistry::class;
    }
}
