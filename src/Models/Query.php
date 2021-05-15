<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    use HasFactory;

    protected $table = 'avt_queries';


    public function arguments()
    {
        return $this->hasMany($this->getConfig()->get('query_argument_model'));
    }
}
