<?php

namespace Anshu8858\VisitorTracker\Commands;

use Illuminate\Console\Command;

class VisitorTrackerCommand extends Command
{
    public $signature = 'visitor-tracker';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
