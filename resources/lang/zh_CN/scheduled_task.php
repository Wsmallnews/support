<?php

return [

    'resource' => [
        'model_label' => '定时任务',
        'plural_model_label' => '定时任务',
        'navigation_label' => '定时任务',
        'navigation_group' => '系统',
    ],

    'status' => [
        'pending' => '待执行',
        'executed' => '已执行',
        'cancelled' => '已取消',
        'failed' => '执行失败',
    ],

    'table' => [
        'column' => [
            'schedulable' => '目标对象',
            'action' => '动作',
            'scheduled_at' => '计划执行时间',
            'status' => '状态',
            'executed_at' => '实际执行时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'no_executed_placeholder' => '未执行',
        ],
        'filter' => [
            'search_placeholder' => '搜索 ID 或动作名称',
            'status_label' => '状态',
            'action_placeholder' => '动作名称',
            'scheduled_at' => '计划执行时间',
            'schedulable_keyword_placeholder' => '按 ID 或标题搜索',
        ],
    ],

    'action' => [
        'view_tasks' => [
            'label' => '定时任务',
        ],
        'delete' => [
            'heading' => '删除定时任务',
            'confirmation' => '确定要删除这条定时任务吗？',
        ],
        'bulk' => [
            'delete' => [
                'confirmation' => '确定要删除选中的定时任务吗？',
            ],
        ],
    ],

    'widget' => [
        'heading' => '定时任务',
    ],

    'infolist' => [
        'tab' => [
            'overview' => '概览',
            'payload' => '参数',
            'raw_data' => '原始数据',
        ],
        'entry' => [
            'action' => '动作',
            'status' => '状态',
            'scheduled_at' => '计划执行时间',
            'executed_at' => '实际执行时间',
            'no_executed' => '未执行',
            'schedulable' => '目标对象',
            'result' => '执行结果',
            'no_result' => '无结果',
            'payload' => '参数',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'key' => '键',
            'value' => '值',
        ],
    ],

    'timeline' => [
        'action' => '动作',
        'scheduled_at' => '计划执行',
        'executed_at' => '实际执行',
        'payload' => '参数',
        'result' => '结果',
        'no_executed' => '未执行',
    ],

    'no_tasks' => '暂无定时任务',
    'no_tasks_desc' => '该记录暂无定时任务。',

];
