<?php

namespace Wsmallnews\Support\Contracts\ActivityLogs;

interface HasActivityLogUrl
{
    public function getActivityLogUrl(): ?string;
}
