<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoIp extends Model
{
    use HasFactory;

    protected $table = 'avt_geoip';
}
