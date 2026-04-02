<?php

return [
    'timeline' => '时间线',

    'table' => [
        'column' => [
            'id' => 'ID',
            'event' => '事件',
            'subject_type' => '对象类型',
            'subject_title' => '对象标题',
            'subject_info' => '对象信息',
            'subject_id' => '对象 ID',
            'description' => '描述',
            'causer_type' => '操作人类型',
            'causer_info' => '操作人信息',
            'causer_name' => '操作人姓名',
            'causer_id' => '操作人 ID',
            'ip_address' => 'IP 地址',
            'browser' => '浏览器',
            'created_at' => '发生时间',
        ],
        'filter' => [
            'only_panel' => '仅显示当前面板的日志',
            'event' => '事件',
            'causer' => '操作人',
            'subject_type' => '对象类型',
            'causer_keyword' => [
                'placeholder' => '请输入操作人姓名或 ID',
            ],
        ],
    ],

    'infolist' => [
        'tab' => [
            'overview' => '概览',
            'changes' => '变更记录',
            'old' => '变更前',
            'new' => '变更后',
            'raw_data' => '原始数据',
        ],
        'entry' => [
            'event' => '事件',
            'created_at' => '发生时间',
            'causer' => '操作人',
            'subject' => '操作对象',
            'ip_address' => 'IP 地址',
            'browser' => '浏览器',
            'description' => '描述',
            'attributes' => '新值',
            'old' => '旧值',
            'key' => '字段',
            'value' => '值',
            'attribute_changes' => '属性变更',
        ],
    ],

    'action' => [
        'timeline' => [
            'label' => '操作时间线',
        ],
        'export' => [
            // 'completed_body' => 'Your activity log export has completed and ' . Number::format($export->successful_rows) . ' ' . str(' row ')->plural($export->successful_rows) . ' exported . ';',
            'completed_body' => '您的活动日志导出已完成，共导出了 :count 行数据。',
            // 'failed_body' => ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
            'failed_body' => ' 共 :fail_count 行数据导出失败',
        ],
        'revert' => [
            'label' => '回滚',
            'helper_text' => '旧值: :old → 新值: :new',
            'subject_not_found' => '找不到关联对象，无法回滚',
            'nothing_selected' => '请至少选择一个要回滚的字段',
            'success' => '回滚成功',
        ],
        'delete' => [
            'heading' => '删除日志',
            'confirmation' => '确定要删除该条日志记录吗？此操作不可撤销。',
            'button' => '确认删除',
        ],
        'bulk' => [
            'delete' => [
                'confirmation' => '确定要删除所选日志记录吗？此操作不可撤销。',
            ],
            'revert' => [
                'label' => '批量回滚',
                'confirmation' => '确定要回滚所选记录吗？每条记录将恢复为变更前的值。',
                'success' => '已成功回滚 :count 条记录',
            ],
        ],
    ],
];
