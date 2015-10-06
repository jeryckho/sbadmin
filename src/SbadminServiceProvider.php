<?php

namespace jeryckho\sbadmin;

use Illuminate\Support\ServiceProvider;

class SbadminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPrepareGenerator();
        $this->registerSimpleaddGenerator();
        // $this->registerMigrationGenerator();
        // $this->registerPivotMigrationGenerator();
    }

    /**
     * Register the sb:prepare generator.
     */
    private function registerPrepareGenerator()
    {
        $this->app->singleton('command.jeryckho.prepare', function ($app) {
            return $app['jeryckho\sbadmin\Commands\PrepareSBCommand'];
        });

        $this->commands('command.jeryckho.prepare');
    }

    /**
     * Register the sb:simpleadd generator.
     */
    private function registerSimpleaddGenerator()
    {
        $this->app->singleton('command.jeryckho.simpleadd', function ($app) {
            return $app['jeryckho\sbadmin\Commands\SimpleaddSBCommand'];
        });

        $this->commands('command.jeryckho.simpleadd');
    }

    /**
     * Register the make:migration generator.
     */
    private function registerMigrationGenerator()
    {
        $this->app->singleton('command.jeryckho.migrate', function ($app) {
            return $app['jeryckho\sbadmin\Commands\MigrationMakeCommand'];
        });

        $this->commands('command.jeryckho.migrate');
    }

    /**
     * Register the make:pivot generator.
     */
    private function registerPivotMigrationGenerator()
    {
        $this->app->singleton('command.jeryckho.migrate.pivot', function ($app) {
            return $app['jeryckho\sbadmin\Commands\PivotMigrationMakeCommand'];
        });

        $this->commands('command.jeryckho.migrate.pivot');
    }
}
