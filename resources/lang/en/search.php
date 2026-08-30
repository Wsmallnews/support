<?php

return [
    'placeholder' => 'Search…',
    'empty' => 'No results found',
    'empty_tip' => 'Try different keywords',

    'exceptions' => [
        'model_required' => 'The search source [:search] is missing the required "model" option.',
        'search_not_found' => 'The search [:search] is not registered.',
        'scout_missing' => 'The scout search engine requires laravel/scout. Install it first: composer require laravel/scout.',
        'engine_unknown' => 'Unknown search engine [:engine]. Supported: "database", "scout", or an engine class name.',
        'scout_trait_missing' => 'The model [:model] must use the Laravel\Scout\Searchable trait for the scout engine; replace it via model config (e.g. config("sn-cms.models.post")) with a subclass that uses the trait.',
    ],
];
