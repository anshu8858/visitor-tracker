<?php

namespace Anshu8858\TrackerVisitor\Models;

class RoutePath extends Base
{
    protected $table = 'avt_route_paths';


    public function parameters()
    {
        return $this->hasMany($this->getConfig()->get('route_path_parameter_model'));
    }

    public function route()
    {
        return $this->belongsTo($this->getConfig()->get('route_model'), 'route_id');
    }
}
