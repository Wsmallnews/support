<?php

return [
    'resource' => [
        'model_label' => '页面',
        'plural_model_label' => '页面',
        'navigation_label' => '页面管理',
    ],

    'form' => [
        'basic_info' => '基础信息',
        'title' => '标题',
        'slug' => '页面标识',
        'slug_helper' => '路由地址的一段（全站唯一），如 about-us 对应 /pages/about-us',
        'composition' => '绑定编排',
        'composition_helper' => '绑定后前台渲染编排内容，忽略页面内容；留空则直接编辑下方页面内容，两者二选一（可先建页面后编排再绑定）',
    ],

    'table' => [
        'title' => '标题',
        'slug' => '标识',
        'composition' => '绑定编排',
        'order' => '排序',
        'status' => '状态',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
        'search_placeholder' => '搜索标题 / 标识',
    ],

    'status' => [
        'draft' => '草稿',
        'published' => '已发布',
        'hidden' => '隐藏',
    ],

    'frontend' => [
        'not_found' => '页面不存在或已下线',
        'empty' => '页面暂无内容',
    ],
];
