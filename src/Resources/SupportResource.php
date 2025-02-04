<?php

namespace Wsmallnews\Support\Resources;

use Filament\Resources\Resource;
use Wsmallnews\Support\Traits\Resources\Scopeable;
use Wsmallnews\Support\Traits\Resources\SetResource;

class SupportResource extends Resource
{
    use Scopeable;
    use SetResource;
}
