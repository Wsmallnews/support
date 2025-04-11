<?php

namespace Wsmallnews\Support\Filament\Resources\BasePages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class BaseCreateRecord extends CreateRecord
{
    use CanScopeable;
}
