<?php

return [
    'resource' => [
        'model_label' => 'Page',
        'plural_model_label' => 'Pages',
        'navigation_label' => 'Pages',
    ],

    'form' => [
        'basic_info' => 'Basic Info',
        'title' => 'Title',
        'slug' => 'Slug',
        'slug_helper' => 'A unique URL segment, e.g. about-us for /pages/about-us',
        'composition' => 'Bound Composition',
        'composition_helper' => 'The frontend renders the bound composition instead of page content; leave empty to edit page content below. Generic compositions only - purpose-slot compositions (e.g. post sidebar) are not listed',
    ],

    'table' => [
        'title' => 'Title',
        'slug' => 'Slug',
        'composition' => 'Composition',
        'order' => 'Order',
        'status' => 'Status',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'search_placeholder' => 'Search title / slug',
    ],

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'hidden' => 'Hidden',
    ],

    'frontend' => [
        'not_found' => 'Page not found or offline',
        'empty' => 'This page has no content yet',
    ],
];
