<?php

namespace jeryckho\sbadmin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;

class PrepareSBCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'sb:prepare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare a new sbadmin template';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        if ( $this->force || $this->confirm( "Are you sure?", false ) ) {
            $this->addViews();
            $this->addPublic();
            $this->addLogic();
            $this->addBower();
            $this->addGulp();
            $this->makeMigrations();
            $this->makeSeeding();
        }
    }

    protected function replaceTpl( $chaine ) {
        return str_replace(
            array(  '[sbnice]',  '[sbn]'),
            array( $this->sbn,  camel_case( $this->sbn )  ),
            $chaine
        );
    }

    protected function makeDirectory($path) {
        if ( !$this->fs->isDirectory( $path ) ) {
            $this->fs->makeDirectory( $path, 0777, true, true );
        }
    }

    private function makeInstall( $titre, $liste ) {
        $exist = false;
        foreach ($liste as $elem) {
            $exist = $exist || $this->fs->exists( $elem[1] );
        }

        if ( $exist && !$this->force && !$this->confirm( $titre . " already exists, overwrite?", false ) ) {
             $this->comment( $titre . ' already exists!');
             return;
        }

        $this->info( 'Installing ' . $titre . '!');

        foreach ($liste as $elem) {
            $this->fs->put( $elem[1], $this->replaceTpl( $this->fs->get( __DIR__ . $elem[0] ) ) );
        }        
    }

    public function __construct(Filesystem $fs, Composer $composer) {
        parent::__construct();

        $this->fs = $fs;
        $this->composer = $composer;
    }

    private function init() {
        $this->force = $this->option('force');
        $this->launch = $this->option('launch');
        $this->sbn = $this->option('sbname');
    }

    protected function makeMigrations() {
        if ($this->launch) {
            $this->call('migrate');
        } else {
            $this->comment( "Launch 'php artisan migrate' to finalize preparation" );
        }
    }

    private function makeSeeding() {
        $liste = array(
            array( '/../stubs/seeds/DatabaseSeeder.stub', './database/seeds/DatabaseSeeder.php' )
        );

        $this->makeInstall( 'Seeding', $liste );

        if ($this->launch) {
            $this->call('db:seed');
        } else {
            $this->comment( "Launch 'php artisan db:seed' to finalize preparation" );
        }        
    }

    private function addLogic() {
        $liste = array(
            array( '/../stubs/Controllers/Auth/AuthController.stub', './app/Http/Controllers/Auth/AuthController.php' ),
            array( '/../stubs/Middleware/Authenticate.stub', './app/Http/Middleware/Authenticate.php' ),
            array( '/../stubs/Middleware/VerifyCsrfToken.stub', './app/Http/Middleware/VerifyCsrfToken.php' ),
            array( '/../stubs/routes.stub', './app/Http/routes.php' )
        );
        $this->makeDirectory("./app/Http/Controllers/Auth/");

        $this->makeInstall( 'Logic', $liste );
    }

    private function addPublic() {
        $liste = array(
            array( '/../stubs/public/dashboard.stub', './public/dashboard.html' ),
            array( '/../stubs/public/js/dash.stub', './public/js/dash.js' ),
            array( '/../stubs/public/js/script.stub', './public/js/script.js' )
        );
        $this->makeDirectory("./public/js/");

        $this->makeInstall( 'Public', $liste );
    }

    private function addViews() {
        $liste = array(
            array( '/../stubs/views/welcome.blade.stub', './resources/views/welcome.blade.php' ),
            array( '/../stubs/views/welcome.blade.stub', './resources/views/welcome.blade.tpl.php' ),
            array( '/../stubs/views/login.blade.stub', './resources/views/login.blade.php' ),
        );

        $this->makeInstall( 'Views', $liste );
    }

    private function addBower() {
        $liste = array(
            array( '/../stubs/bower.stub', './bower.json'),
            array( '/../stubs/bowerrc.stub', './.bowerrc')
        );

        $this->makeInstall( 'Bower', $liste );
        
        if ($this->launch) {
            exec('npm install bower');
            exec('bower install');
        } else {
             $this->comment( "Launch 'bower install' to finalize preparation" );
        }
    }

    private function addGulp() {
        $liste = array(
            array( '/../stubs/package.stub', './package.json'),
            array( '/../stubs/gulpfile.stub', './gulpfile.js')
        );

        $this->makeInstall( 'Gulp', $liste );
        
        if ($this->launch) {
            exec('npm install gulp');
            exec('npm install');
            exec('gulp');
        } else {
             $this->comment( "Launch 'npm install' and 'gulp' to finalize preparation" );
        }
    }

    protected function getOptions() {
        return array(
            array('force', 'f', InputOption::VALUE_NONE, 'Force preparation' , null),
            array('launch','l', InputOption::VALUE_NONE, 'Launch needed commands' , null),
            array('sbname','sn', InputOption::VALUE_OPTIONAL, 'Template Name' , env('SB_NAME', 'My App') )
        );
    }   
}
