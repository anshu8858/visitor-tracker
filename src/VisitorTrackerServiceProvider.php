<?php

namespace Anshu8858\VisitorTracker;

use Anshu8858\VisitorTracker\Commands\VisitorTrackerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
