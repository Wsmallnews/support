<?php

namespace Wsmallnews\Support\Filament\Resources\BasePages;

use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Support\Traits\Resources\Pages\CanScopeable;

class BaseEditRecord extends EditRecord
{
    use CanScopeable;
}
