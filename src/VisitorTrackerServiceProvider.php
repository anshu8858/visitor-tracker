<?php

namespace Anshu8858\VisitorTracker;

use Illuminate\Foundation\AliasLoader as IlluminateAliasLoader;

use Anshu8858\VisitorTracker\Commands\VisitorTrackerCommand;
use Anshu8858\VisitorTracker\Eventing\EventStorage;
use Anshu8858\VisitorTracker\Models\Agent;

use Anshu8858\VisitorTracker\Services\VisitorTrackerMgr;

use Anshu8858\VisitorTracker\Models\Connection;
use Anshu8858\VisitorTracker\Models\Cookie;
use Anshu8858\VisitorTracker\Models\Device;
use Anshu8858\VisitorTracker\Models\Domain;
use Anshu8858\VisitorTracker\Models\Error;
use Anshu8858\VisitorTracker\Models\Event;
use Anshu8858\VisitorTracker\Models\EventLog;
use Anshu8858\VisitorTracker\Models\GeoIp as GeoIpModel;
use Anshu8858\VisitorTracker\Models\Language;
use Anshu8858\VisitorTracker\Models\Log;
use Anshu8858\VisitorTracker\Models\Path;
use Anshu8858\VisitorTracker\Models\Query;
use Anshu8858\VisitorTracker\Models\QueryArgument;
use Anshu8858\VisitorTracker\Models\Referer;
use Anshu8858\VisitorTracker\Models\Route;
use Anshu8858\VisitorTracker\Models\RoutePath;
use Anshu8858\VisitorTracker\Models\RoutePathParameter;
use Anshu8858\VisitorTracker\Models\Session;
use Anshu8858\VisitorTracker\Models\SqlQuery;
use Anshu8858\VisitorTracker\Models\SqlQueryBinding;
use Anshu8858\VisitorTracker\Models\SqlQueryBindingParameter;
use Anshu8858\VisitorTracker\Models\SqlQueryLog;
use Anshu8858\VisitorTracker\Models\SystemClass;

use Anshu8858\VisitorTracker\Http\Ctrlr\Connection as ConnectionCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Cookie as CookieCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Device as DeviceCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Domain as DomainCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Error as ErrorCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Event as EventCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\EventLog as EventLogCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\GeoIp as GeoIpModel as GeoIpModelCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Language as LanguageCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Log as LogCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Path as PathCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Query as QueryCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\QueryArgument as QueryArgumentCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Referer as RefererCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Route as RouteCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\RoutePath as RoutePathCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\RoutePathParameter as RoutePathParameterCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\Session as SessionCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\SqlQuery as SqlQueryCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\SqlQueryBinding as SqlQueryBindingCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\SqlQueryBindingParameter as SqlQueryBindingParameterCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\SqlQueryLog as SqlQueryLogCtrlr;
use Anshu8858\VisitorTracker\Http\Ctrlr\SystemClass as SystemClassCtrlr;

use Anshu8858\VisitorTracker\Repositories\Message as MessageRepository;
use Anshu8858\VisitorTracker\Services\Authentication;
use Anshu8858\VisitorTracker\Support\Cache;

use Anshu8858\VisitorTracker\Support\CrawlerDetector;
use Anshu8858\VisitorTracker\Support\Exceptions\Handler as TrackerExceptionHandler;
use Anshu8858\VisitorTracker\Support\LanguageDetect;
use Anshu8858\VisitorTracker\Support\MobileDetect;
use Anshu8858\VisitorTracker\Support\UserAgentParser;
use Anshu8858\VisitorTracker\Vendor\Laravel\Artisan\Tables as TablesCommand;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\PhpSession;
use PragmaRX\Tracker\Vendor\Laravel\Artisan\UpdateGeoIp;

use Illuminate\Support\ServiceProvider;

class VisitorTrackerServiceProvider extends ServiceProvider
{    
    protected $repositoryManagerIsBooted = false;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    protected $userChecked = false;
    protected $avt;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // routes
        //if (!$this->app->routesAreCached()) {
        //    require __DIR__ . '/Http/routes.php';
        //}

        // views
        //$this->loadViewsFrom(__DIR__ . '/Views', 'visitlog');

        // publish our files over to main laravel app
        $this->publishes([
            __DIR__ . '/config/avt.php' => config_path('avt.php'),
            __DIR__ . '/database/migrations' => database_path('migrations/avt/')
        ]);



        $key = $this->packageName . ('enabled' ? '.' . 'enabled' : '');
        if (! $this->app['config']->get($key)) {
            return false;
        }

        //$this->loadRoutes();
        $this->registerErrorHandler();

        $key = $this->packageName . ('use_middleware' ? '.' . 'use_middleware' : '');
        if (! $this->app['config']->get($key)) {
            $this->bootTracker();
        }

    }

    /**
     * Check if the service provider is full booted.
     *
     * @return void
     */
    public function isFullyBooted()
    {
        return $this->repositoryManagerIsBooted;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->getConfig('enabled')) {
            $this->registerCache();
            $this->registerCtrlr();
            $this->registerTracker();
            $this->registerTablesCommand();
            $this->registerUpdateGeoIpCommand();
            $this->registerExecutionCallback();
            $this->registerUserCheckCallback();
            $this->registerSqlQueryLogWatcher();
            $this->registerGlobalEventLogger();
            $this->registerMessageRepository();
            //$this->registerGlobalViewComposers();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return ['avt'];
    }

    /**
     * Takes all the components of Tracker and glues them
     * together to create Tracker.
     *
     * @return void
     */
    protected function registerTracker()
    {
        $this->app->singleton('avt', function ($app) {
            $app['avt.loaded'] = true;

            return new Tracker(
                $app['avt.config'],
                $app['avt.mgr'],
                $app['request'],
                $app['router'],
                $app['log'],
                $app,
                $app['avt.msg']
            );
        });
    }

    public function registerCtrlr()
    {
        $this->app->singleton('avt.mgr', function ($app) {
            try {
                $uaParser = new UserAgentParser($app->make('path.base'));
            } catch (\Exception $exception) {
                $uaParser = null;
            }

            $logMdl = new Log;
            $pathMdl = new Path;
            $errorMdl = new Error;
            $eventMdl = new Event;
            $agentMdl = new Agent;
            $deviceMdl = new Device;
            $cookieMdl = new Cookie;
            $domainMdl = new Domain;
            $refererMdl = new Referer;
            $geoipMdl = new GeoIpModel;
            $sessionModel = new Session;
            $eventLogMdl = new EventLog;
            $languageMdl = new Language;
            $systemClassMdl = new SystemClass;
            $connectionMdl = new Connection;
            $refererSearchTermMdl = new RefererSearchTerm;

            $routeMdl = new Route;
            $routePathMdl = new RoutePath;
            $routePathParameterMdl = new RoutePathParameter;

            $queryMdl = new Query;
            $sqlQueryMdl = new SqlQuery;
            $queryArgumentMdl = new QueryArgument;
            $sqlQueryLogMdl = new SqlQueryLog;
            $sqlQueryBindingMdl = new SqlQueryBinding;
            $sqlQueryBindingParameterMdl = new SqlQueryBindingParameter;

            // Ctrlr
            
            $logCtrlr = new LogCtrlr($logMdl);
            $connectionCtrlr = new ConnectionCtrlr($connectionMdl);
            $sqlQueryBindingCtrlr = new SqlQueryBindingCtrlr($sqlQueryBindingMdl);
            $sqlQueryBindingParameterCtrlr = new SqlQueryBindingParameterCtrlr($sqlQueryBindingParameterMdl);
            $sqlQueryLogCtrlr = new SqlQueryLogCtrlr($sqlQueryLogMdl);

            $sqlQueryCtrlr = new SqlQueryCtrlr(
                $sqlQueryMdl,
                $sqlQueryLogCtrlr,
                $sqlQueryBindingCtrlr,
                $sqlQueryBindingParameterCtrlr,
                $connectionCtrlr,
                $logCtrlr,
                $app['avt.config']
            );

            $routeCtrlr = new RouteCtrlr($routeMdl, $app['avt.config']);
            $systemClassCtrlr = new SystemClassCtrlr($systemClassMdl);
            $eventLogCtrlr = new EventLogCtrlr($eventLogMdl);
            $eventCtrlr = new EventCtrlr(
                $eventMdl,
                $app['avt.events'],
                $eventLogCtrlr,
                $systemClassCtrlr,
                $logCtrlr,
                $app['avt.config']
            );


            $crawlerDetect = new CrawlerDetector(
                $app['request']->headers->all(),
                $app['request']->server('HTTP_USER_AGENT')
            );

            $manager = new VisitorTrackerMgr(
                new GeoIp($this->getConfig('geoip_database_path')),
                new MobileDetect(),
                $uaParser,
                $app['session.store'],
                $app['avt.config'],
                new SessionCtrlr($sessionMdl, $app['avt.config'], new PhpSession()),
                $logCtrlr,
                new PathCtrlr($pathMdl),
                new QueryCtrlr($queryMdl),
                new QueryArgumentCtrlr($queryArgumentMdl),
                new AgentCtrlr($agentMdl),
                new DeviceCtrlr($deviceMdl),
                new CookieCtrlr($cookieMdl, $app['avt.config'], $app['request'], $app['cookie']),
                new DomainCtrlr($domainMdl),
                new RefererCtrlr($refererMdl, $refererSearchTermMdl, $this->getAppUrl(), $app->make('Anshu8858\VisitorTracker\Support\RefererParser')),
                $routeCtrlr,
                new RoutePathCtrlr($routePathMdl),
                new RoutePathParameterCtrlr($routePathParameterMdl),
                new ErrorCtrlr($errorMdl),
                new GeoIpCtrlr($geoipMdl),
                $sqlQueryCtrlr,
                $sqlQueryBindingCtrlr,
                $sqlQueryBindingParameterCtrlr,
                $sqlQueryLogCtrlr,
                $connectionCtrlr,
                $eventCtrlr,
                $eventLogCtrlr,
                $systemClassCtrlr,
                $crawlerDetect,
                new LanguageCtrlr($languageMdl),
                new LanguageDetect()
            );

            $this->repositoryManagerIsBooted = true;

            return $manager;
        });
    }


    public function registerCache()
    {
        $this->app->singleton('avt.cache', function ($app) {
            return new Cache($app['avt.config'], $app);
        });
    }

    protected function registerTablesCommand()
    {
        $this->app->singleton('avt.tables.command', function ($app) {
            return new TablesCommand();
        });

        $this->commands('avt.tables.command');
    }

    protected function registerExecutionCallback()
    {
        $me = $this;

        $mathingEvents = [
            'router.matched',
            'Illuminate\Routing\Events\RouteMatched',
        ];

        $this->app['events']->listen($mathingEvents, function () use ($me) {
            $me->getTracker()->routerMatched($me->getConfig('log_routes'));
        });
    }

    protected function registerErrorHandler()
    {
        if ($this->getConfig('log_exceptions')) {
            $illuminateHandler = 'Illuminate\Contracts\Debug\ExceptionHandler';

            $handler = new TrackerExceptionHandler(
                $this->getTracker(),
                $this->app[$illuminateHandler]
            );

            // Replace original Illuminate Exception Handler by Tracker's
            $this->app[$illuminateHandler] = $handler;
        }
    }


    protected function registerSqlQueryLogWatcher()
    {
        $me = $this;

        if (! class_exists('Illuminate\Database\Events\QueryExecuted')) {
            $this->app['events']->listen('illuminate.query', function (
                $query,
                $bindings,
                $time,
                $name
            ) use ($me) {
                $me->logSqlQuery($query, $bindings, $time, $name);
            });
        } else {
            $this->app['events']->listen('Illuminate\Database\Events\QueryExecuted', function ($query) use ($me) {
                $me->logSqlQuery($query);
            });
        }
    }

    /**
     * @param $query
     * @param $bindings
     * @param $time
     * @param $name
     * @param $me
     */
    public function logSqlQuery($query, $bindings = null, $time = null, $connectionName = null)
    {
        if ($this->getTracker()->isEnabled()) {
            if ($query instanceof \Illuminate\Database\Events\QueryExecuted) {
                $bindings = $query->bindings;
                $time = $query->time;
                $connectionName = $query->connectionName;
                $query = $query->sql;
            }

            $this->getTracker()->logSqlQuery($query, $bindings, $time, $connectionName);
        }
    }

    protected function registerGlobalEventLogger()
    {
        $me = $this;

        $this->app->singleton('avt.events', function ($app) {
            return new EventStorage();
        });

        $this->app['events']->listen('*', function ($object = null) use ($me) {
            if ($me->app['avt.events']->isOff() || ! $me->isFullyBooted()) {
                return;
            }

            // To avoid infinite recursion, event tracking while logging events
            // must be turned off
            $me->app['avt.events']->turnOff();

            // Log events even before application is ready
            // $me->app['avt.events']->logEvent(
            //    $me->app['events']->firing(),
            //    $object
            // );
            // TODO: we have to investigate a way of doing this

            // Can only send events to database after application is ready
            if (isset($me->app['avt.loaded'])) {
                $me->getTracker()->logEvents();
            }

            // Turn the event tracking to on again
            $me->app['avt.events']->turnOn();
        });
    }


    /**
     * Get the current package directory.
     *
     * @return string
     */
    public function getPackageDir()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..';
    }

    /**
     * Boot & Track.
     */
    protected function bootTracker()
    {
        $this->getTracker()->boot();
    }


    protected function registerUpdateGeoIpCommand()
    {
        $this->app->singleton('avt.updategeoip.command', function ($app) {
            return new UpdateGeoIp();
        });

        $this->commands('avt.updategeoip.command');
    }


    /**
     * @return Tracker
     */
    public function getTracker()
    {
        if (! $this->avt) {
            $this->avt = $this->app['avt'];
        }

        return $this->avt;
    }

    public function getRootDirectory()
    {
        return __DIR__.'/../..';
    }

    protected function getAppUrl()
    {
        return $this->app['request']->url();
    }

    /**
     * Register the message repository.
     */
    protected function registerMessageRepository()
    {
        $this->app->singleton('avt.messages', function () {
            return new MessageRepository();
        });
    }



    protected function getRootDirectory()
    {
        return $this->getPackageDir();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishFiles();

        $this->loadViews();
    }

    /**
     * Boot for the child ServiceProvider
     *
     * @return void
     */
    protected function preRegister()
    {
        if (!$this->registered) {
            $this->loadHelper();

            $this->mergeConfig();

            $this->registerNamespace();

            $this->registerConfig();

            $this->registerFilesystem();

            $this->registered = true;
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->preRegister();
    }

    /**
     * Get a configuration value
     *
     * @param  string $key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        // Waiting for https://github.com/laravel/framework/pull/7440
        // return $this->app['config']->get("{$this->packageVendor}.{$this->packageName}.config.{$key}");

        $key = $this->packageName . ($key ? '.' . $key : '');

        return $this->app['config']->get($key);
    }

    /**
     * Register the configuration object
     *
     * @return void
     */
    private function registerConfig()
    {
        $this->app->singleton($this->packageName . '.config', function ($app) {
            // Waiting for https://github.com/laravel/framework/pull/7440
            // return new Config($app['config'], $this->packageNamespace . '.config.');

            return new Config($app['config'], $this->packageNamespace . '.');
        });
    }

    /**
     * Register the Filesystem driver used by the child ServiceProvider
     *
     * @return void
     */
    private function registerFileSystem()
    {
        $this->app->singleton($this->packageName . '.fileSystem', function ($app) {
            return new Filesystem;
        });
    }

    public function registerServiceAlias($name, $class)
    {
        IlluminateAliasLoader::getInstance()->alias($name, $class);
    }

    public function registerServiceProvider($class)
    {
        $this->app->register($class);
    }

    private function publishFiles()
    {
        if (file_exists($configFile = $this->getRootDirectory() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
            $this->publishes(
                [$configFile => config_path($this->packageName . '.php')],
                'config'
            );
        }

        if (file_exists($migrationsPath = $this->getRootDirectory() . DIRECTORY_SEPARATOR . 'migrations')) {
            $this->publishes(
                [$migrationsPath => base_path('database' . DIRECTORY_SEPARATOR . 'migrations')],
                'migrations'
            );
        }
    }

    private function mergeConfig()
    {
        if (file_exists($configFile = $this->getRootDirectory() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
            $this->mergeConfigFrom(
                $configFile, $this->packageName
            );
        }
    }

    private function registerNamespace()
    {
        // Waiting for https://github.com/laravel/framework/pull/7440
        // $this->packageNamespace = "$this->packageVendor.$this->packageName";

        $this->packageNamespace = $this->packageName;
    }

    private function loadViews()
    {
        if (file_exists($viewsFolder = $this->getRootDirectory() . DIRECTORY_SEPARATOR . 'views')) {
            $this->loadViewsFrom($viewsFolder, "{$this->packageVendor}/{$this->packageName}");
        }
    }

    private function loadHelper()
    {
        require_once('helpers.php');
    }
}
