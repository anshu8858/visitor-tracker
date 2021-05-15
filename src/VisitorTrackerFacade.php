<?php

namespace Anshu8858\VisitorTracker;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Anshu8858\VisitorTracker\VisitorTracker
 */
class VisitorTrackerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'visitor-tracker';
    }
}
