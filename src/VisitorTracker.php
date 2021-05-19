<?php

namespace Anshu8858\VisitorTracker;

use Anshu8858\VisitorTracker\Service\VisitorTrackerMgr;
use Anshu8858\VisitorTracker\Repositories\Message as MessageRepo;
use Anshu8858\VisitorTracker\Support\Minutes;

use PragmaRX\Support\Config;
use PragmaRX\Support\GeoIp\Updater as GeoIpUpdater;
use PragmaRX\Support\IpAddress;

use Illuminate\Foundation\Application as Laravel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Psr\Log\LoggerInterface;

class VisitorTracker
{
    protected $config;

    /**
     * @var \Illuminate\Routing\Router
     */
    protected $route;
    protected $logger;

    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;
    protected $enabled = true;
    protected $sessionData;
    protected $loggedItems = [];
    protected $booted = false;

    /**
     * @var MessageRepository
     */
    protected $msgRepo;

    public function __construct(
        Config $config,
        VisitorTrackerMgr $vtm,
        Request $req,
        Router $route,
        LoggerInterface $logger,
        Laravel $laravel,
        MessageRepo $msgRepo
    ) {
        $this->config = $config;
        $this->vtm = $vtm;
        $this->request = $req;
        $this->route = $route;
        $this->logger = $logger;
        $this->laravel = $laravel;
        $this->msgRepo = $msgRepo;
    }

    public function allSessions()
    {
        return $this->vtm->getAllSessions();
    }

    public function boot()
    {
        if ($this->booted) {
            return false;
        }

        $this->booted = true;

        if ($this->isTrackable()) {
            $this->track();
        }

        return true;
    }

/*
    public function checkCurrentUser()
    {
        if (! $this->sessionData['user_id'] && $user_id = $this->getUserId()) {
            return true;
        }

        return false;
    }
*/
    public function currentSession()
    {
        return $this->vtm->sessionRepository->getCurrent();
    }

    protected function deleteCurrentLog()
    {
        $this->vtm->logRepository->delete();
    }

    public function errors($minutes, $results = true)
    {
        return $this->vtm->errors(Minutes::make($minutes), $results);
    }

    public function events($minutes, $results = true)
    {
        return $this->vtm->events(Minutes::make($minutes), $results);
    }

    public function getAgentId()
    {
        return $this->config->get('log_user_agents')
            ? $this->vtm->getAgentId()
            : null;
    }

    public function getConfig($key)
    {
        return $this->config->get($key);
    }

    public function getCookieId()
    {
        return $this->config->get('store_cookie_tracker')
            ? $this->vtm->getCookieId()
            : null;
    }

    public function getDeviceId()
    {
        return $this->config->get('log_devices')
            ? $this->vtm->findOrCreateDevice(
                $this->vtm->getCurrentDeviceProperties()
            )
            : null;
    }

    public function getLanguageId()
    {
        return $this->config->get('log_languages')
            ? $this->vtm->findOrCreateLanguage($this->vtm->getCurrentLanguage())
            : null;
    }

    public function getDomainId($domain)
    {
        return $this->vtm->getDomainId($domain);
    }

    public function getGeoIpId()
    {
        return $this->config->get('log_geoip')
            ? $this->vtm->getGeoIpId($this->request->getClientIp())
            : null;
    }

    /**
     * @return array
     */
    public function getLogData()
    {
        return [
            'session_id' => $this->getSessionId(true),
            'method' => $this->request->method(),
            'path_id' => $this->getPathId(),
            'query_id' => $this->getQueryId(),
            'referer_id' => $this->getRefererId(),
            'is_ajax' => $this->request->ajax(),
            'is_secure' => $this->request->isSecure(),
            'is_json' => $this->request->isJson(),
            'wants_json' => $this->request->wantsJson(),
        ];
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getPathId()
    {
        return $this->config->get('log_paths')
            ? $this->vtm->findOrCreatePath(
                [
                    'path' => $this->request->path(),
                ]
            )
            : null;
    }

    public function getQueryId()
    {
        if ($this->config->get('log_queries')) {
            if (count($arguments = $this->request->query())) {
                return $this->vtm->getQueryId(
                    [
                        'query' => array_implode('=', '|', $arguments),
                        'arguments' => $arguments,
                    ]
                );
            }
        }
    }

    public function getRefererId()
    {
        return $this->config->get('log_referers')
            ? $this->vtm->getRefererId(
                $this->request->headers->get('referer')
            )
            : null;
    }

    public function getRoutePathId()
    {
        return $this->vtm->getRoutePathId($this->route, $this->request);
    }

    protected function logUntrackable($item)
    {
        if ($this->config->get('log_untrackable_sessions') && ! isset($this->loggedItems[$item])) {
            $this->getLogger()->warning('TRACKER (unable to track item): '.$item);

            $this->loggedItems[$item] = $item;
        }
    }

    /**
     * @return array
     */
    protected function makeSessionData()
    {
        $sessionData = [
            'user_id' => $this->getUserId(),
            'device_id' => $this->getDeviceId(),
            'client_ip' => $this->request->getClientIp(),
            'geoip_id' => $this->getGeoIpId(),
            'agent_id' => $this->getAgentId(),
            'referer_id' => $this->getRefererId(),
            'cookie_id' => $this->getCookieId(),
            'language_id' => $this->getLanguageId(),
            'is_robot' => $this->isRobot(),

            // The key user_agent is not present in the sessions table, but
            // it's internally used to check if the user agent changed
            // during a session.
            'user_agent' => $this->vtm->getCurrentUserAgent(),
        ];

        return $this->sessionData = $this->vtm->checkSessionData($sessionData, $this->sessionData);
    }

    public function getSessionId($updateLastActivity = false)
    {
        return $this->vtm->getSessionId(
            $this->makeSessionData(),
            $updateLastActivity
        );
    }

    public function getUserId()
    {
        return $this->config->get('log_users')
            ? $this->vtm->getCurrentUserId()
            : null;
    }

    /**
     * @param \Throwable $throwable
     */
    public function handleThrowable($throwable)
    {
        if ($this->config->get('log_enabled')) {
            $this->vtm->handleThrowable($throwable);
        }
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function isRobot()
    {
        return $this->vtm->isRobot();
    }

    protected function isSqlQueriesLoggableConnection($name)
    {
        return ! in_array(
            $name,
            $this->config->get('do_not_log_sql_queries_connections')
        );
    }

    public function isTrackable()
    {
        return $this->config->get('enabled') &&
                $this->logIsEnabled() &&
                $this->allowConsole() &&
                $this->parserIsAvailable() &&
                $this->isTrackableIp() &&
                $this->isTrackableEnvironment() &&
                $this->routeIsTrackable() &&
                $this->pathIsTrackable() &&
                $this->notRobotOrTrackable();
    }

    public function isTrackableEnvironment()
    {
        $trackable = ! in_array(
            $this->laravel->environment(),
            $this->config->get('do_not_track_environments')
        );

        if (! $trackable) {
            $this->logUntrackable('environment '.$this->laravel->environment().' is not trackable.');
        }

        return $trackable;
    }

    public function isTrackableIp()
    {
        $trackable = ! IpAddress::ipv4InRange(
            $ipAddress = $this->request->getClientIp(),
            $this->config->get('do_not_track_ips')
        );

        if (! $trackable) {
            $this->logUntrackable($ipAddress.' is not trackable.');
        }

        return $trackable;
    }

    public function logByRouteName($name, $minutes = null)
    {
        if ($minutes) {
            $minutes = Minutes::make($minutes);
        }

        return $this->vtm->logByRouteName($name, $minutes);
    }

    public function logEvents()
    {
        if (
            $this->isTrackable() &&
            $this->config->get('log_enabled') &&
            $this->config->get('log_events')
        ) {
            $this->vtm->logEvents();
        }
    }

    public function logIsEnabled()
    {
        $enabled =
            $this->config->get('log_enabled') ||
            $this->config->get('log_sql_queries') ||
            $this->config->get('log_sql_queries_bindings') ||
            $this->config->get('log_events') ||
            $this->config->get('log_geoip') ||
            $this->config->get('log_user_agents') ||
            $this->config->get('log_users') ||
            $this->config->get('log_devices') ||
            $this->config->get('log_languages') ||
            $this->config->get('log_referers') ||
            $this->config->get('log_paths') ||
            $this->config->get('log_queries') ||
            $this->config->get('log_routes') ||
            $this->config->get('log_exceptions');

        if (! $enabled) {
            $this->logUntrackable('there are no log items enabled.');
        }

        return $enabled;
    }

    public function logSqlQuery($query, $bindings, $time, $name)
    {
        if (
            $this->isTrackable() &&
            $this->config->get('log_enabled') &&
            $this->config->get('log_sql_queries') &&
            $this->isSqlQueriesLoggableConnection($name)
        ) {
            $this->vtm->logSqlQuery($query, $bindings, $time, $name);
        }
    }

    protected function notRobotOrTrackable()
    {
        $trackable =
            ! $this->isRobot() ||
            ! $this->config->get('do_not_track_robots');

        if (! $trackable) {
            $this->logUntrackable('tracking of robots is disabled.');
        }

        return $trackable;
    }

    public function pageViews($minutes, $results = true)
    {
        return $this->vtm->pageViews(Minutes::make($minutes), $results);
    }

    public function pageViewsByCountry($minutes, $results = true)
    {
        return $this->vtm->pageViewsByCountry(Minutes::make($minutes), $results);
    }

    public function allowConsole()
    {
        return
            (! $this->laravel->runningInConsole()) ||
            $this->config->get('console_log_enabled', false);
    }

    public function parserIsAvailable()
    {
        if (! $this->vtm->parserIsAvailable()) {
            $this->logger->error(trans('tracker::tracker.regex_file_not_available'));

            return false;
        }

        return true;
    }

    public function routeIsTrackable()
    {
        if (! $this->route) {
            return false;
        }

        if (! $trackable = $this->vtm->routeIsTrackable($this->route)) {
            $this->logUntrackable('route '.$this->route->getCurrentRoute()->getName().' is not trackable.');
        }

        return $trackable;
    }

    public function pathIsTrackable()
    {
        if (! $trackable = $this->vtm->pathIsTrackable($this->request->path())) {
            $this->logUntrackable('path '.$this->request->path().' is not trackable.');
        }

        return $trackable;
    }

    public function routerMatched($log)
    {
        if ($this->vtm->routeIsTrackable($this->route)) {
            if ($log) {
                $this->vtm->updateRoute(
                    $this->getRoutePathId()
                );
            }
        }
        // Router was matched but this route is not trackable
        // Let's just delete the stored data, because There's not a
        // realy clean way of doing this because if a route is not
        // matched, and this happens ages after the app is booted,
        // we till need to store data from the request.
        else {
            $this->turnOff();
            $this->deleteCurrentLog();
        }
    }

    public function sessionLog($uuid, $results = true)
    {
        return $this->vtm->getSessionLog($uuid, $results);
    }

    public function sessions($minutes = 1440, $results = true)
    {
        return $this->vtm->getLastSessions(Minutes::make($minutes), $results);
    }

    public function onlineUsers($minutes = 3, $results = true)
    {
        return $this->sessions(3);
    }

    public function track()
    {
        $log = $this->getLogData();

        if ($this->config->get('log_enabled')) {
            $this->vtm->createLog($log);
        }
    }

    public function trackEvent($event)
    {
        $this->vtm->trackEvent($event);
    }

    public function trackVisit($route, $req)
    {
        $this->vtm->trackRoute($route, $req);
    }

    public function turnOff()
    {
        $this->enabled = false;
    }

    public function userDevices($minutes, $user_id = null, $results = true)
    {
        return $this->vtm->userDevices(
            Minutes::make($minutes),
            $user_id,
            $results
        );
    }

    public function users($minutes, $results = true)
    {
        return $this->vtm->users(Minutes::make($minutes), $results);
    }

    /**
     * Get the messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages()
    {
        return $this->msgRepo->getMessages();
    }

    /**
     * Update the GeoIp2 database.
     *
     * @return bool
     */
    public function updateGeoIp()
    {
        $updater = new GeoIpUpdater();
        $success = $updater->updateGeoIpFiles($this->config->get('geoip_database_path'));
        $this->msgRepo->addMessage($updater->getMessages());

        return $success;
    }
}
