<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Models\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Wsmallnews\Support\Enums\ActivityLogEvent;

/**
 * 通用活动日志 Trait（已内置 LogsActivity）
 *
 * 使用方法：
 * 1. 在 Model 中 use HasActivityLog;
 * 2. 覆盖以下方法来自定义行为：
 *    - getActivityTitleAttribute(): 返回标题字段名，默认 'name'
 *    - getActivityIgnoreAttributes(): 返回忽略变动的字段数组
 *    - static $recordEvents: 记录的事件，默认 null（全部）
 */
trait HasActivityLog
{
    use LogsActivity {
        LogsActivity::getActivitylogOptions as spatieGetActivitylogOptions;
    }

    /**
     * 获取日志标题字段名，子类可覆盖.
     */
    protected function getActivityTitleAttribute(): string
    {
        return 'name';
    }

    /**
     * 获取忽略变动的字段，子类可覆盖.
     */
    protected function getActivityIgnoreAttributes(): array
    {
        return ['order_column', 'updated_at'];
    }

    /**
     * 获取活动日志选项.
     * 
     * @return LogOptions 活动日志选项
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly($this->getActivityIgnoreAttributes())
            ->setDescriptionForEvent(fn (string $eventName) => $this->getActivityDescription($eventName));
    }

    /**
     * 生成日志描述文本.
     * 
     * @param string $eventName 事件名称
     * @return string 日志描述文本
     */
    protected function getActivityDescription(string $eventName): string
    {
        $label = ActivityLogEvent::tryFrom($eventName)?->getLabel() ?? Str::studly($eventName);
        $title = $this->{$this->getActivityTitleAttribute()} ?? $this->getKey();

        return "{$label}: {$title}";
    }
}
