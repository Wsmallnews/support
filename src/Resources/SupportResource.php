<?php

namespace Wsmallnews\Support\Resources;

use Filament\Resources\Resource;
use Wsmallnews\Support\Traits\Resources\SetResource;
use Wsmallnews\Support\Traits\Resources\Scopeable;

class SupportResource extends Resource
{
    use SetResource;
    use Scopeable;
}
