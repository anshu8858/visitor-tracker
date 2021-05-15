<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SqlQueryBindingParameter extends Model
{
    use HasFactory;

    protected $table = 'avt_sql_query_bindings_parameters';
}
