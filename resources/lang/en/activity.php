<?php

return [
    'timeline' => 'Timeline',

    'event' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],

    'activity_log_resource' => [
        'model_label' => 'Activity Log',
        'plural_model_label' => 'Activity Logs',
        'navigation_group' => 'System Management',
        'navigation_label' => 'Activity Log Management',
    ],

    'table' => [
        'column' => [
            'event' => 'Event',
            'subject_type' => 'Subject Type',
            'subject_title' => 'Subject Title',
            'subject_info' => 'Subject Info',
            'subject_id' => 'Subject ID',
            'description' => 'Description',
            'causer_type' => 'Causer Type',
            'causer_info' => 'Causer Info',
            'causer_name' => 'Causer Name',
            'causer_id' => 'Causer ID',
            'ip_address' => 'IP Address',
            'browser' => 'Browser',
            'created_at' => 'Occurred At',
            'created_filter_label' => 'Occurred',
        ],
        'filter' => [
            'only_panel' => 'Only show logs for current panel',
            'event' => 'Event',
            'causer' => 'Causer',
            'subject_type' => 'Subject Type',
            'causer_keyword' => [
                'placeholder' => 'Please enter causer name or ID',
            ],
        ],
    ],

    'infolist' => [
        'tab' => [
            'overview' => 'Overview',
            'changes' => 'Changes',
            'old' => 'Before',
            'new' => 'After',
            'raw_data' => 'Raw Data',
        ],
        'entry' => [
            'event' => 'Event',
            'created_at' => 'Occurred At',
            'causer' => 'Causer',
            'subject' => 'Subject',
            'ip_address' => 'IP Address',
            'browser' => 'Browser',
            'description' => 'Description',
            'attributes' => 'New Values',
            'old' => 'Old Values',
            'key' => 'Field',
            'value' => 'Value',
            'attribute_changes' => 'Changes',
        ],
    ],

    'action' => [
        'timeline' => [
            'label' => 'Activity Timeline',
        ],
        'export' => [
            'completed_body' => 'Your activity log export has completed and :count rows exported.',
            'failed_body' => ' :fail_count rows failed to export',
        ],
        'revert' => [
            'label' => 'Revert',
            'helper_text' => 'Old: :old → New: :new',
            'subject_not_found' => 'Cannot find associated subject, unable to revert',
            'nothing_selected' => 'Please select at least one field to revert',
            'success' => 'Revert successful',
        ],
        'delete' => [
            'heading' => 'Delete Log',
            'confirmation' => 'Are you sure you want to delete this log entry? This action is irreversible.',
            'button' => 'Confirm Delete',
        ],
        'bulk' => [
            'delete' => [
                'confirmation' => 'Are you sure you want to delete the selected log entries? This action is irreversible.',
            ],
            'revert' => [
                'label' => 'Bulk Revert',
                'confirmation' => 'Are you sure you want to revert the selected entries? Each entry will be restored to its previous values.',
                'success' => 'Successfully reverted :count entries',
            ],
        ],
    ],

    'no_activity' => 'No activity logs',
    'no_activity_desc' => 'No activity logs.',
];
