<?php

namespace Anshu8858\VisitorTracker;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Anshu8858\VisitorTracker\Commands\VisitorTrackerCommand;

class VisitorTrackerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('visitor-tracker')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_visitor-tracker_table')
            ->hasCommand(VisitorTrackerCommand::class);
    }
}
