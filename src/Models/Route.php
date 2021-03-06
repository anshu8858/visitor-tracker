<?php

namespace Anshu8858\TrackerVisitor\Models;

use PragmaRX\Support\Config;

class Route extends Base
{
    protected $table = 'avt_routes';


    public function __construct($model, Config $config)
    {
        parent::__construct($model);

        $this->config = $config;
    }

    public function isTrackable($route)
    {
        $forbidden = $this->config->get('do_not_track_routes');

        return
            ! $forbidden ||
            ! $route->currentRouteName() ||
            ! in_array_wildcard($route->currentRouteName(), $forbidden);
    }

    public function pathIsTrackable($path)
    {
        $forbidden = $this->config->get('do_not_track_paths');

        return
            ! $forbidden ||
            empty($path) ||
            ! in_array_wildcard($path, $forbidden);
    }


    public function paths()
    {
        return $this->hasMany($this->getConfig()->get('route_path_model'));
    }
}
