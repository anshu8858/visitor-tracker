<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutePath extends Model
{
    use HasFactory;

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
