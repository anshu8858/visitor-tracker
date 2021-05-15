# Track visits on a page and visitor also

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anshu8858/visitor-tracker.svg?style=flat-square)](https://packagist.org/packages/anshu8858/visitor-tracker)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/anshu8858/visitor-tracker/run-tests?label=tests)](https://github.com/anshu8858/visitor-tracker/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/anshu8858/visitor-tracker/Check%20&%20fix%20styling?label=code%20style)](https://github.com/anshu8858/visitor-tracker/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/anshu8858/visitor-tracker.svg?style=flat-square)](https://packagist.org/packages/anshu8858/visitor-tracker)


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/visitor-tracker.jpg?t=1" width="419px" />](https://github.com/anshu8858/visitor-tracker)

## Installation

You can install the package via composer:

```bash
composer require anshu8858/visitor-tracker
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Anshu8858\VisitorTracker\VisitorTrackerServiceProvider" --tag="visitor-tracker-migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Anshu8858\VisitorTracker\VisitorTrackerServiceProvider" --tag="visitor-tracker-config"
```

This is the contents of the published config file:

```php
 'enabled' => false,

    'cache_enabled' => true, /* Enable cache? */
    'use_middleware' => false, /* Deffer booting for middleware use */
    'do_not_track_robots' => false, /* Robots should be tracked? */

    /*
     * Which environments are not trackable?
     */
    'do_not_track_environments' => [
        // defaults to none
    ],

    /*
     * Which routes names are not trackable?
     */
    'do_not_track_routes' => [
        'tracker.stats.*',
    ],

    /*
     * Which route paths are not trackable?
     */
    'do_not_track_paths' => [
        'api/*',
    ],

    /*
     * The Do Not Track Ips is used to disable Tracker for some IP addresses:
     *
     *     '127.0.0.1', '192.168.1.1'
     *
     * You can set ranges of IPs
     *     '192.168.0.1-192.168.0.100'
     *
     * And use net masks
     *     '10.0.0.0/32'
     *     '172.17.0.0/255.255.0.0'
     */
    'do_not_track_ips' => [
        '127.0.0.0/24', /// range 127.0.0.1 - 127.0.0.255
    ],

    /*
     * When an IP is not trackable, show a message in log.
     */
    'log_untrackable_sessions' => true,

    /*
     * Log every single access?
     *
     * The log table can become huge if your site is popular, but...
     *
     * Log table is also responsible for storing information on:
     *
     *    - Routes and controller actions accessed
     *    - HTTP method used (GET, POST...)
     *    - Error log
     *    - URL queries (including values)
     */
    'log_enabled' => false,
    /*
    'console_log_enabled' => false, //Log artisan commands?
    */
    /*
     * Log SQL queries?
     * Log must be enabled for this option to work.
     */
    'log_sql_queries' => false,

    /*
     * If you prefer to store Tracker data on a different database or connection,
     * you can set it here.
     *
     * To avoid SQL queries log recursion, create a different connection for Tracker,
     * point it to the same database (or not) and forbid logging of this connection in
     * do_not_log_sql_queries_connections.
     */
    'connection' => 'tracker',

    /*
     * Forbid logging of SQL queries for some connections.
     *
     * To avoid recursion, you better ignore Tracker connection here.
     *
     * Please create a separate database connection for Tracker. It can hit
     * the same database of your application, but the connection itself
     * has to have a different name, so the package can ignore its own queries
     * and avoid recursion.
     *
     */
    'do_not_log_sql_queries_connections' => [
        'tracker',
    ],

    /*
     * GeoIp2 database path.
     * To get a fresh version of this file, use the command
     *      "php artisan tracker:updategeoip"
     */

    'geoip_database_path' => __DIR__.'/geoip', //storage_path('geoip'),

    /*
     * Also log SQL query bindings?
     * Log must be enabled for this option to work.
     */
    'log_sql_queries_bindings' => false,

    'log_events' => false, /* Log events? */

    /*
     * Which events do you want to log exactly?
     */
    'log_only_events' => [
        // defaults to logging all events
    ],

    /*
     * What are the names of the id columns on your system?
     *
     * 'id' is the most common, but if you have one or more different,
     * please add them here in your preference order.
     */
    'id_columns_names' => [
        'id',
    ],
    /*
     * Do not log events for the following patterns.
     * Strings accepts wildcards:
     *
     *    eloquent.*
     *
     */
    'do_not_log_events' => [
        'illuminate.log',
        'eloquent.*',
        'router.*',
        'composing: *',
        'creating: *',
    ],

    /*
     * Do you wish to log Geo IP data?
     * You will need to install the geoip package
     *     composer require "geoip/geoip":"~1.14"
     *
     * And remove the PHP module
     *     sudo apt-get purge php5-geoip
     */
    'log_geoip' => false,

    'log_user_agents' => false, /* Do you wish to log the user agent? */
    'log_users' => false, /* Do you wish to log your users? */
    'log_devices' => false, /* Do you wish to log devices? */
    'log_languages' => false, /* Do you wish to log languages? */
    'log_referers' => false, /* Do you wish to log HTTP referers? */
    'log_paths' => false, /* Do you wish to log url paths? */
    'log_queries' => false, /* Do you wish to log url queries and query arguments? */
    'log_routes' => false, /* Do you wish to log routes and route parameters? */
    'log_exceptions' => false, /* Log errors and exceptions? */

    /*
     * A cookie may be created on your visitor device, so you can have information
     * on everything made using that device on your site.	 *
     */
    'store_cookie_tracker' => false,

    /*
     * If you are storing cookies, you better change it to a name you of your own.
     */
    'tracker_cookie_name' => 'please_change_this_cookie_name',

    /*
     * Internal tracker session name.
     */
    'tracker_session_name' => 'tracker_session',

    /*
     * Laravel internal variables on user authentication and login.
     */
    'authentication_ioc_binding' => ['auth'], // defaults to 'auth' in Illuminate\Support\Facades\Auth

    'authentication_guards' => [], // defaults to []
    'authenticated_check_method' => 'check', // to Auth::check()
    'authenticated_user_method' => 'user', // to Auth::user()
    'authenticated_user_id_column' => 'id', // to Auth::user()->id
    'authenticated_user_username_column' => 'email', // to Auth::user()->email
    
    /*
     * Set a default user agent
     */
    'default_user_agent' => '',
];
```

## Usage

```php
$vt = new Anshu8858\VisitorTracker();
echo $vt->echoPhrase('Hello, Anshu!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anshu Kushawaha](https://github.com/anshu8858)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
