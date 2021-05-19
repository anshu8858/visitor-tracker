<?php

namespace Anshu8858\TrackerVisitor\Models;

class Query extends Base
{
    protected $table = 'avt_queries';

    public function arguments()
    {
        return $this->hasMany($this->getConfig()->get('query_argument_model'));
    }
}
