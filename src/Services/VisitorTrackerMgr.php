<?php

namespace Anshu8858\VisitorTracker\Services;

use Anshu8858\VisitorTracker\Models\Agent;
use Anshu8858\VisitorTracker\Models\Connection;
use Anshu8858\VisitorTracker\Models\Cookie;
use Anshu8858\VisitorTracker\Models\Device;
use Anshu8858\VisitorTracker\Models\Domain;
use Anshu8858\VisitorTracker\Models\Error;
use Anshu8858\VisitorTracker\Models\Event;
use Anshu8858\VisitorTracker\Models\EventLog;
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

use Anshu8858\VisitorTracker\Support\CrawlerDetector;
use Anshu8858\VisitorTracker\Support\LanguageDetect;

use Anshu8858\VisitorTracker\Support\MobileDetect;
use Illuminate\Routing\Router as IlluminateRouter;

use Illuminate\Session\Store as IlluminateSession;
use PragmaRX\Support\Config;
use PragmaRX\Support\GeoIp\GeoIp;

class VisitorTrackerMgr
{
    private $geoIp;
    private $userAgentParser;
    private $crawlerDetector;
    private $languageDetect;

    private $pathMdl;
    private $queryMdl;
    private $domainMdl;
    private $refererMdl;
    private $routeMdl;
    private $routePathMdl;
    private $errorMdl;
    private $geoIpMdl;
    private $sqlQueryMdl;
    private $sqlQueryLogMdl;
    private $sqlQueryBindingMdl;
    private $queryArgumentMdl;
    private $routePathParameterMdl;
    private $sqlQueryBindingParameterMdl;
    private $connectionMdl;
    private $eventMdl;
    private $eventLogMdl;
    private $systemClassMdl;
    private $languageMdl;

    /**
     * @param \PragmaRX\Tracker\Support\UserAgentParser|null $userAgentParser
     */
    public function __construct(
        $userAgentParser,
        GeoIP $geoIp,
        Config $config,
        MobileDetect $mobileDetect,
        Authentication $authentication,
        IlluminateSession $session,
        Session $sessionMdl,
        Log $logMdl,
        Path $pathMdl,
        Query $queryMdl,
        QueryArgument $queryArgumentMdl,
        Agent $agentMdl,
        Device $deviceMdl,
        Cookie $cookieMdl,
        Domain $domainMdl,
        Referer $refererMdl,
        Route $routeMdl,
        RoutePath $routePathMdl,
        RoutePathParameter $routePathParameterMdl,
        Error $errorMdl,
        GeoIpMdl $geoIpMdl,
        SqlQuery $sqlQueryMdl,
        SqlQueryBinding $sqlQueryBindingMdl,
        SqlQueryBindingParameter $sqlQueryBindingParameterMdl,
        SqlQueryLog $sqlQueryLogMdl,
        Connection $connectionMdl,
        Event $eventMdl,
        EventLog $eventLogMdl,
        SystemClass $systemClassMdl,
        CrawlerDetector $crawlerDetector,
        Language $languageMdl,
        LanguageDetect $languageDetect
    ) {
        $this->mobileDetect = $mobileDetect;
        $this->userAgentParser = $userAgentParser;

        $this->sessionMdl = $sessionMdl;
        $this->session = $session;
        $this->config = $config;
        $this->geoIp = $geoIp;
        $this->logMdl = $logMdl;
        $this->pathMdl = $pathMdl;
        $this->queryMdl = $queryMdl;
        $this->agentMdl = $agentMdl;
        $this->deviceMdl = $deviceMdl;
        $this->cookieMdl = $cookieMdl;
        $this->domainMdl = $domainMdl;
        $this->refererMdl = $refererMdl;
        $this->routeMdl = $routeMdl;
        $this->errorMdl = $errorMdl;
        $this->eventMdl = $eventMdl;
        $this->connectionMdl = $connectionMdl;
        $this->eventLogMdl = $eventLogMdl;
        $this->systemClassMdl = $systemClassMdl;
        $this->languageMdl = $languageMdl;
        $this->geoIpMdl = $geoIpMdl;
        $this->routePathMdl = $routePathMdl;
        $this->routePathParameterMdl = $routePathParameterMdl;
        $this->sqlQueryMdl = $sqlQueryMdl;
        $this->sqlQueryLogMdl = $sqlQueryLogMdl;
        $this->queryArgumentMdl = $queryArgumentMdl;
        $this->sqlQueryBindingMdl = $sqlQueryBindingMdl;
        $this->sqlQueryBindingParameterMdl = $sqlQueryBindingParameterMdl;

        $this->crawlerDetector = $crawlerDetector;
        $this->languageDetect = $languageDetect;
    }

    public function checkSessionData($newData, $currentData)
    {
        if ($newData && $currentData && $newData !== $currentData) {
            $newData = $this->updateSessionData($newData);
        }

        return $newData;
    }

    public function createLog($data)
    {
        $this->logMdl->createLog($data);
        $this->sqlQueryMdl->fire();
    }

    private function createRoutePathParameter($route_path_id, $parameter, $value)
    {
        return $this->routePathParameterMdl->create(
            [
                'route_path_id' => $route_path_id,
                'parameter' => $parameter,
                'value' => $value,
            ]
        );
    }

    public function errors($minutes, $results)
    {
        return $this->logMdl->getErrors($minutes, $results);
    }

    public function events($minutes, $results)
    {
        return $this->eventMdl->getAll($minutes, $results);
    }

    public function findOrCreateAgent($data)
    {
        return $this->agentMdl->findOrCreate($data, ['name_hash']);
    }

    public function findOrCreateDevice($data)
    {
        return $this->deviceMdl->findOrCreate($data, ['kind', 'model', 'platform', 'platform_version']);
    }

    public function findOrCreateLanguage($data)
    {
        return $this->languageMdl->findOrCreate($data, ['preference', 'language-range']);
    }

    public function findOrCreatePath($path)
    {
        return $this->pathMdl->findOrCreate($path, ['path']);
    }

    public function findOrCreateQuery($data)
    {
        $id = $this->queryMdl->findOrCreate($data, ['query'], $created);

        if ($created) {
            foreach ($data['arguments'] as $argument => $value) {
                if (is_array($value)) {
                    $value = multi_implode(',', $value);
                }

                $this->queryArgumentMdl->create(
                    [
                        'query_id' => $id,
                        'argument' => $argument,
                        'value' => empty($value) ? '' : $value,
                    ]
                );
            }
        }

        return $id;
    }

    public function findOrCreateSession($data)
    {
        return $this->sessionMdl->findOrCreate($data, ['uuid']);
    }

    public function getAgentId()
    {
        return $this->findOrCreateAgent($this->getCurrentAgentArray());
    }

    public function getAllSessions()
    {
        return $this->sessionMdl->all();
    }

    public function getCookieId()
    {
        return $this->cookieMdl->getId();
    }

    public function getCurrentAgentArray()
    {
        return [
            'name' => $name = $this->getCurrentUserAgent() ?: 'Other',
            'browser' => $this->userAgentParser->userAgent->family,
            'browser_version' => $this->userAgentParser->getUserAgentVersion(),
            'name_hash' => hash('sha256', $name),
        ];
    }

    public function getCurrentDeviceProperties()
    {
        if ($properties = $this->getDevice()) {
            $properties['platform'] = $this->getOperatingSystemFamily();
            $properties['platform_version'] = $this->getOperatingSystemVersion();
        }

        return $properties;
    }

    public function getCurrentUserAgent()
    {
        return $this->userAgentParser->originalUserAgent;
    }


    /**
     * @return array
     */
    private function getDevice()
    {
        try {
            return $this->mobileDetect->detectDevice();
        } catch (\Exception $e) {
            return;
        }
    }

    public function getDomainId($domain)
    {
        return $this->domainMdl->findOrCreate(
            ['name' => $domain],
            ['name']
        );
    }

    public function getGeoIpId($clientIp)
    {
        $id = null;

        if ($geoIpData = $this->geoIp->searchAddr($clientIp)) {
            $id = $this->geoIpMdl->findOrCreate(
                $geoIpData,
                ['latitude', 'longitude']
            );
        }

        return $id;
    }

    public function getLastSessions($minutes, $results)
    {
        return $this->sessionMdl->last($minutes, $results);
    }

    private function getOperatingSystemFamily()
    {
        try {
            return $this->userAgentParser->operatingSystem->family;
        } catch (\Exception $e) {
            return;
        }
    }

    private function getOperatingSystemVersion()
    {
        try {
            return $this->userAgentParser->getOperatingSystemVersion();
        } catch (\Exception $e) {
            return;
        }
    }

    public function getQueryId($query)
    {
        if (! $query) {
            return;
        }

        return $this->findOrCreateQuery($query);
    }

    public function getRefererId($referer)
    {
        if ($referer) {
            $url = parse_url($referer);

            if (! isset($url['host'])) {
                return;
            }

            $parts = explode('.', $url['host']);
            $domain = array_pop($parts);

            if (count($parts) > 0) {
                $domain = array_pop($parts).'.'.$domain;
            }

            $domain_id = $this->getDomainId($domain);
            
            return $this->refererMdl->store($referer, $url['host'], $domain_id);
        }
    }

    /**
     * @param $request
     *
     * @return mixed
     */
    private function getRequestPath($request)
    {
        if (is_string($request)) {
            return $request;
        }

        if (is_array($request)) {
            return $request['path'];
        }

        return $request->path();
    }

    /**
     * @param $route
     *
     * @return mixed
     */
    private function getRouteAction($route)
    {
        if (is_string($route)) {
            return '';
        }

        if (is_array($route)) {
            return $route['action'];
        }

        return $route->currentRouteAction();
    }

    /**
     * @param string $name
     */
    private function getRouteId($name, $action)
    {
        return $this->routeMdl->findOrCreate(
            ['name' => $name, 'action' => $action],
            ['name', 'action']
        );
    }

    /**
     * @param $route
     *
     * @return string
     */
    private function getRouteName($route)
    {
        if (is_string($route)) {
            return $route;
        }

        if (is_array($route)) {
            return $route['name'];
        }

        if ($name = $route->current()->getName()) {
            return $name;
        }

        $action = $route->current()->getAction();

        if ($name = isset($action['as']) ? $action['as'] : null) {
            return $name;
        }

        return '/'.$route->current()->uri();
    }

    /**
     * @param bool $created
     */
    private function getRoutePath($route_id, $path, &$created = null)
    {
        return $this->routePathMdl->findOrCreate(
            ['route_id' => $route_id, 'path' => $path],
            ['route_id', 'path'],
            $created
        );
    }

    public function getRoutePathId($route, $request)
    {
        $route_id = $this->getRouteId(
            $this->getRouteName($route),
            $this->getRouteAction($route)
                ?: 'closure'
        );

        $created = false;

        $route_path_id = $this->getRoutePath(
            $route_id,
            $this->getRequestPath($request),
            $created
        );

        if ($created && $route instanceof IlluminateRouter && $route->current()) {
            foreach ($route->current()->parameters() as $parameter => $value) {
                // When the parameter value is a whole model, we have
                // two options left:
                //
                //  1) Return model id, if it's available as 'id'
                //  2) Return null (not ideal, but, what could we do?)
                //
                // Should we store the whole model? Not really useful, right?

                if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                    $model_id = null;

                    foreach ($this->config->get('id_columns_names', ['id']) as $column) {
                        if (property_exists($value, $column)) {
                            $model_id = $value->$column;

                            break;
                        }
                    }

                    $value = $model_id;
                }

                if ($route_path_id && $parameter && $value) {
                    $this->createRoutePathParameter($route_path_id, $parameter, $value);
                }
            }
        }

        return $route_path_id;
    }

    public function getSessionId($sessionData, $updateLastActivity)
    {
        return $this->sessionMdl->getCurrentId($sessionData, $updateLastActivity);
    }

    public function getSessionLog($uuid, $results = true)
    {
        $session = $this->sessionMdl->findByUuid($uuid);

        return $this->logMdl->bySession($session->id, $results);
    }

    public function handleThrowable($throwable)
    {
        $error_id = $this->errorMdl->findOrCreate(
            [
                'message' => $this->errorMdl->getMessageFromThrowable($throwable),
                'code' => $this->errorMdl->getCodeFromThrowable($throwable),
            ],
            ['message', 'code']
        );

        return $this->logMdl->updateError($error_id);
    }

    public function isRobot()
    {
        return $this->crawlerDetector->isRobot();
    }

    public function logByRouteName($name, $minutes = null)
    {
        return $this->logMdl->allByRouteName($name, $minutes);
    }

    public function logEvents()
    {
        $this->eventMdl->logEvents();
    }

    public function logSqlQuery($query, $bindings, $time, $name)
    {
        $this->sqlQueryMdl->push([
            'query' => $query,
            'bindings' => $bindings,
            'time' => $time,
            'name' => $name,
        ]);
    }

    public function pageViews($minutes, $results)
    {
        return $this->logMdl->pageViews($minutes, $results);
    }

    public function pageViewsByCountry($minutes, $results)
    {
        return $this->logMdl->pageViewsByCountry($minutes, $results);
    }

    public function parserIsAvailable()
    {
        return ! empty($this->userAgentParser);
    }

    public function routeIsTrackable($route)
    {
        return $this->routeMdl->isTrackable($route);
    }

    public function pathIsTrackable($path)
    {
        return $this->routeMdl->pathIsTrackable($path);
    }

    public function setSessionData($data)
    {
        $this->sessionMdl->setSessionData($data);
    }

    public function trackEvent($event)
    {
        $this->eventMdl->logEvent($event);
    }

    public function trackRoute($route, $request)
    {
        $this->updateRoute(
            $this->getRoutePathId($route, $request)
        );
    }

    public function updateRoute($route_id)
    {
        return $this->logMdl->updateRoute($route_id);
    }

    public function updateSessionData($data)
    {
        return $this->sessionMdl->updateSessionData($data);
    }

    public function userDevices($minutes, $user_id, $results)
    {
        return $this->sessionMdl->userDevices(
            $minutes,
            $user_id ?: $this->authentication->getCurrentUserId(),
            $results
        );
    }

    public function users($minutes, $results)
    {
        return $this->sessionMdl->users($minutes, $results);
    }
}
