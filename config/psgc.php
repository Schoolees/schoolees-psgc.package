<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PSGC API Prefix
    |--------------------------------------------------------------------------
    |
    | This value determines the prefix used for all PSGC API routes.
    | Example: 'psgc' → /psgc/regions
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
    | Route Registration Strategy
    |--------------------------------------------------------------------------
    |
    | register_package_routes:
    |   true  => service provider auto-registers package routes/psgc.php
    |   false => you manage routes manually (e.g., publish routes/psgc.php)
    |
    | append_include_on_publish:
    |   true  => psgc:publish-routes appends a route group to routes/web.php
    |   false => only publish routes/psgc.php, no automatic include append
    |
    */
    'register_package_routes' => true,
    'append_include_on_publish' => false,

    /*
    |--------------------------------------------------------------------------
    | Response Format
    |--------------------------------------------------------------------------
    |
    | datatable  => legacy DataTables-shaped payload
    | pagination => generic API pagination payload with data/meta/links
    |
    */
    'response_format' => 'datatable',

    /*
    |--------------------------------------------------------------------------
    | Response Formatter
    |--------------------------------------------------------------------------
    |
    | Optional hook to shape the response envelope yourself. It receives the
    | resource collection and must return an array. Accepts a callable,
    | 'Class@method', [Class::class, 'method'], or a class name that is either
    | invokable or exposes dataTableResponse(). When set, it replaces both
    | built-in envelopes.
    |
    */
    'response_formatter' => null,

    /*
    |--------------------------------------------------------------------------
    | Filters Echo
    |--------------------------------------------------------------------------
    |
    | Controls the `filters` key in the response.
    |
    | request => echo the whole query string back (default; unvalidated)
    | applied => echo only the filters that actually reached the query
    | none    => omit the echo entirely
    |
    */
    'filters_echo' => 'request',

    /*
    |--------------------------------------------------------------------------
    | Exception Logging
    |--------------------------------------------------------------------------
    |
    | Controllers convert every Throwable into a JSON envelope. With this on,
    | 5xx responses are also written to the log, so an internal failure is not
    | silently swallowed.
    |
    */
    'log_exceptions' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    |
    | Number of results returned per page in API responses.
    |
    */
    'paginate' => 10,
    'max_limit' => 100,

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
    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | PSGC data only changes when the dataset is re-seeded, so lookups are safe
    | to cache for long periods. Off by default.
    |
    | store => cache store to use, or null for the application default
    | ttl   => seconds to keep an entry (default one day)
    |
    | The seeder invalidates the cache automatically. To do it by hand, run
    | `php artisan psgc:cache-clear`.
    |
    */
    'cache' => [
        'enabled' => env('PSGC_CACHE', false),
        'store'   => env('PSGC_CACHE_STORE'),
        'ttl'     => 86400,
    ],

    'resources_path' => base_path('resources/psgc'),
    'truncate_before_seed' => true,

];
