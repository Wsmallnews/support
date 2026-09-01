<?php

return [
    'placeholder' => '搜索…',
    'empty' => '没有找到相关内容',
    'empty_tip' => '换个关键词试试',

    'results_title' => '搜索结果',
    'results_tip' => '输入关键词开始搜索',

    'exceptions' => [
        'model_required' => '注册搜索来源 [:module] 缺少 model 选项。',
        'search_not_found' => '搜索 [:module] 未注册。',
        'scout_missing' => '使用 scout 搜索引擎需先安装 laravel/scout（composer require laravel/scout）。',
        'engine_unknown' => '未知的搜索引擎 [:engine]，支持 "database"、"scout" 或引擎实现类名。',
        'scout_trait_missing' => '模型 [:model] 使用 scout 搜索引擎需 use Laravel\Scout\Searchable；可通过模型配置（如 config("sn-cms.models.post")）替换为 use 了该 trait 的子类。',
    ],
];
