<?php

return [

    'resource' => [
        'model_label' => 'Scheduled Task',
        'plural_model_label' => 'Scheduled Tasks',
        'navigation_label' => 'Scheduled Tasks',
        'navigation_group' => 'System',
    ],

    'status' => [
        'pending' => 'Pending',
        'executed' => 'Executed',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
    ],

    'table' => [
        'column' => [
            'schedulable' => 'Target',
            'action' => 'Action',
            'scheduled_at' => 'Scheduled At',
            'status' => 'Status',
            'executed_at' => 'Executed At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'no_executed_placeholder' => 'Not Executed',
        ],
        'filter' => [
            'search_placeholder' => 'Search by ID or action',
            'status_label' => 'Status',
            'action_placeholder' => 'Action name',
            'scheduled_at' => 'Scheduled At',
            'schedulable_keyword_placeholder' => 'Search by ID or title',
        ],
    ],

    'action' => [
        'view_tasks' => [
            'label' => 'Scheduled Tasks',
        ],
        'delete' => [
            'heading' => 'Delete Scheduled Task',
            'confirmation' => 'Are you sure you want to delete this scheduled task?',
        ],
        'bulk' => [
            'delete' => [
                'confirmation' => 'Are you sure you want to delete the selected scheduled tasks?',
            ],
        ],
    ],

    'widget' => [
        'heading' => 'Scheduled Tasks',
    ],

    'infolist' => [
        'tab' => [
            'overview' => 'Overview',
            'payload' => 'Payload',
            'raw_data' => 'Raw Data',
        ],
        'entry' => [
            'action' => 'Action',
            'status' => 'Status',
            'scheduled_at' => 'Scheduled At',
            'executed_at' => 'Executed At',
            'no_executed' => 'Not Executed',
            'schedulable' => 'Target',
            'result' => 'Result',
            'no_result' => 'No Result',
            'payload' => 'Payload',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'key' => 'Key',
            'value' => 'Value',
        ],
    ],

    'timeline' => [
        'action' => 'Action',
        'scheduled_at' => 'Scheduled At',
        'executed_at' => 'Executed At',
        'payload' => 'Payload',
        'result' => 'Result',
        'no_executed' => 'Not Executed',
    ],

    'no_tasks' => 'No Scheduled Tasks',
    'no_tasks_desc' => 'No scheduled tasks for this record.',

];
