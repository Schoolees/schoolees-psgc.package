<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PSGC API Prefix
    |--------------------------------------------------------------------------
    |
    | This value determines the prefix used for all PSGC API routes.
    | Example: 'psgc' → /api/psgc/regions
    |
    */
    'api_prefix' => env('PSGC_API_PREFIX', 'psgc'),

    /*
    |--------------------------------------------------------------------------
    | PSGC Middleware
    |--------------------------------------------------------------------------
    |
    | This array of middleware will be applied to all PSGC API routes.
    | By default, it uses the `api` middleware group.
    | You can add authentication or other middleware here.
    |
    */
    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    |
    | Number of results returned per page in API responses.
    |
    */
    'paginate' => 10,

    /*
    |--------------------------------------------------------------------------
    | Default Ordering
    |--------------------------------------------------------------------------
    |
    | 'order_by' → column to sort results by
    | 'sort_by'  → sort direction ('asc' or 'desc')
    |
    */
    'order_by' => 'name',
    'sort_by'  => 'asc',

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Map model table names here for flexibility in case your
    | database uses different table names.
    |
    */
    'tables' => [
        'regions'   => 'regions',
        'provinces' => 'provinces',
        'cities'    => 'cities',
        'barangays' => 'barangays',
    ],

    /*
    |--------------------------------------------------------------------------
    | PSGC Seeder Options
    |--------------------------------------------------------------------------
    |
    | Path for the PSGC JSON resource files and whether the seeder
    | should truncate existing records before inserting new ones.
    |
    */
    'resources_path' => base_path('resources/psgc'),
    'truncate_before_seed' => true,

];
