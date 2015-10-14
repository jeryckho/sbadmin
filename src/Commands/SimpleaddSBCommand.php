<?php

namespace jeryckho\sbadmin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SimpleaddSBCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'sb:simpleadd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a templated resource';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        if ( $this->force || $this->confirm( "Are you sure?", false ) ) {

            $this->addController();
            $this->addPublic();
            $this->addModel();
            $this->addViews();

            $this->modRoutes();
            $this->modTemplate();
            $this->modPublic();

            $this->launchGulp(); 
        }
    }

    protected function replaceTpl( $chaine ) {
        return str_replace(
            array(  '[Rsr]',     '[res]',     '[ress]',     '[table]',     '[fres]',     '[fress]',     '[ico]',     '[sbnice]',  '[sbn]'),
            array( $this->Rsr,  $this->res,  $this->ress,  $this->table,  $this->fres,  $this->fress,  $this->ico,  $this->sbn,  camel_case( $this->sbn )  ),
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
        $this->Rsr = $this->argument('name');
        $this->res = $this->option('resource') ? $this->option('resource') : mb_strtolower( $this->Rsr );
        $this->ress = $this->option('resources') ? $this->option('resources') : str_plural( $this->res );
        $this->table = $this->option('table') ? $this->option('table') : $this->ress;
        $this->fres = $this->option('localized') ? $this->option('localized') : $this->Rsr;
        $this->fress = $this->option('localizeds') ? $this->option('localizeds') : str_plural( $this->fres );
        $this->ico = $this->option('icon');
        $this->sbn = $this->option('sbname');
    }

    private function modRoutes() {
        $liste = array(
            './app/Http/routes.php' 
        );

        $this->info( 'Modifying Routes!');

        $pre = "/* Don't touch above */";
        $pst = $this->replaceTpl( "\tRoute::resource('[res]', '[Rsr]Controller');" );

        foreach ($liste as $elem) {
            $crt = $this->fs->get( $elem );
            if ( strpos( $crt, $pst ) === false ) {
                $crt = str_replace( $pre, $pre . "\n" . $pst, $crt );    
            }
            $this->fs->put( $elem, $crt );
        }
    }

    private function modTemplate() {
        $liste = array(
            './resources/views/Tpl/welcome.blade.php' 
        );

        $this->info( 'Modifying Template!');

        $pre = "<!-- Don't touch above -->";
        $pst = $this->replaceTpl( "\t" . '<script src="js/Tpl/[res].js"></script>' );

        foreach ($liste as $elem) {
            $crt = $this->fs->get( $elem );
            if ( strpos( $crt, $pst ) === false ) {
                $crt = str_replace( $pre, $pre . "\n" . $pst, $crt );    
            }
            $this->fs->put( $elem, $crt );
        }
    }

    private function modPublic() {
        $liste = array(
            './public/js/Tpl/script.js' 
        );

        $this->info( 'Modifying Public!');

        $pre = "// Don't touch above";
        $pst = $this->replaceTpl( "\t\t" . "'User' : " . '$resource' . "( 'user/:Id', { Id : '@Id' }, { 'update' : { method : 'PUT' } } )," );

        foreach ($liste as $elem) {
            $crt = $this->fs->get( $elem );
            if ( strpos( $crt, $pst ) === false ) {
                $crt = str_replace( $pre, $pre . "\n" . $pst, $crt );    
            }
            $this->fs->put( $elem, $crt );
        }
    }

    private function addController() {
        $liste = array(
            array( '/../stubs/Controllers/RsrController.stub', $this->replaceTpl( './app/Http/Controllers/[Rsr]Controller.php' ) )
        );

        $this->makeInstall( 'Controller', $liste );
    }

    private function addModel() {
        $liste = array(
            array( '/../stubs/Rsr.stub', $this->replaceTpl( './app/[Rsr].php' ) )
        );

        $this->makeInstall( 'Model', $liste );
    }

    private function addPublic() {
        $liste = array(
            array( '/../stubs/public/js/res.stub', $this->replaceTpl( './public/js/Tpl/[res].js' ) )
        );
        $this->makeDirectory( './public/js/' );
        $this->makeDirectory( './public/js/Tpl/' );

        $this->makeInstall( 'Public', $liste );
    }

    private function addViews() {
        $liste = array(
            array( '/../stubs/views/rsr.index.blade.stub', $this->replaceTpl( './resources/views/[res]/index.blade.php' ) ),
            array( '/../stubs/views/rsr.show.blade.stub', $this->replaceTpl( './resources/views/[res]/show.blade.php' ) )
        );
        $this->makeDirectory( $this->replaceTpl( './resources/views/[res]/' ) );

        $this->makeInstall( 'Views', $liste );
    } 

    private function launchGulp() {
        if ($this->launch) {
            exec('gulp');
        } else {
             $this->comment( "Launch 'gulp' to finalize action" );
        }
    }

    protected function getArguments() {
        return array(
            array('name', InputArgument::REQUIRED, 'Name of the resource to add' , null)
        );
    }

    protected function getOptions() {
        return array(
            array('force', 'f', InputOption::VALUE_NONE, 'Force preparation' , null),
            array('launch','l', InputOption::VALUE_NONE, 'Launch needed commands' , null),
            array('resource','r', InputOption::VALUE_OPTIONAL, 'Lowercase singular name' , null),
            array('resources','rs', InputOption::VALUE_OPTIONAL, 'Lowercase plural name' , null),
            array('table','t', InputOption::VALUE_OPTIONAL, 'Name of the table' , null),
            array('localized','fr', InputOption::VALUE_OPTIONAL, 'Singular localized name' , null),
            array('localizeds','frs', InputOption::VALUE_OPTIONAL, 'Plural localized name' , null),
            array('icon','i', InputOption::VALUE_OPTIONAL, 'Icon class' , ''),
            array('sbname','sn', InputOption::VALUE_OPTIONAL, 'Template Name' , env('SB_NAME', 'My App') )
        );
    }
}
