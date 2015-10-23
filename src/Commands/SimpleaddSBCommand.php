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
            $this->upgradeModel();
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
        $this->fres = $this->option('localized') ? mb_convert_encoding( $this->option('localized'), "utf-8", "windows-1252") : $this->Rsr;
        $this->fress = $this->option('localizeds') ? mb_convert_encoding( $this->option('localizeds'), "utf-8", "windows-1252") : str_plural( $this->fres );
        $this->ico = $this->option('icon');
        $this->sbn = $this->option('sbname');
    }

    private function upgradeModel() {
        $liste = array(
            $this->replaceTpl( './app/[Rsr].php' )
        );

        $this->info( 'Upgrading Model!');

        foreach ($liste as $elem) {
            $crt = $this->fs->get( $elem );

            $org = 'extends Model';
            $dst = 'extends VModel';
            if ( strpos( $crt, $org ) !== false ) {
                $crt = str_replace( $org, $dst, $crt );
            }

            $anchor = 'protected $table';
            if ( strpos( $crt, $anchor ) !== false ) {
                $org = 'public $timestamps';
                if ( strpos( $crt, $org ) === false ) {
                    $crt = str_replace( $anchor, $org . " = true;\n\t" .  $anchor, $crt );
                }

                $orgk = 'protected $hidden';
                $orgv = 'protected $visible';
                if ( ( strpos( $crt, $orgk ) === false ) && ( strpos( $crt, $orgv ) === false ) ) {
                    $crt = str_replace( $anchor, $orgk . " = [];\n\t// " . $orgv . " = [];\n\t" . $anchor, $crt );
                }

                $orgk = 'protected $guarded';
                $orgv = 'protected $fillable';
                if ( ( strpos( $crt, $orgk ) === false ) && ( strpos( $crt, $orgv ) === false ) ) {
                    $crt = str_replace( $anchor, $orgk . " = [];\n\t// " . $orgv . " = [];\n\t" . $anchor, $crt );
                }
            }

            $org = 'public $timestamps';
            if ( strpos( $crt, $org ) !== false ) {
                $crt = str_replace( $org, 'protected $dateFormat = '. "'U';\n\t" .  $org, $crt );
            }

            $this->fs->put( $elem, $crt );
        }
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

        $pstB = "<!-- Don't touch below -->";
        $preB = $this->replaceTpl( "\t\t\t\t\t\t<li>\n\t\t\t\t\t\t\t".'<a href="#/[Rsr]"><i class="[ico] fa-fw"></i> [fress]</a>'."\n\t\t\t\t\t\t</li>" );

        foreach ($liste as $elem) {
            $crt = $this->fs->get( $elem );
            if ( strpos( $crt, $pst ) === false ) {
                $crt = str_replace( $pre, $pre . "\n" . $pst, $crt );    
            }
            if ( strpos( $crt, $preB ) === false ) {
                $crt = str_replace( $pstB, $preB . "\n" . $pstB, $crt );    
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
        $pst = $this->replaceTpl( "\t\t" . "'[Rsr]' : " . '$resource' . "( '[res]/:Id', { Id : '@Id' }, { 'update' : { method : 'PUT' } } )," );

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
            exec('gulp dev');
        } else {
             $this->comment( "Launch 'gulp dev' to finalize action" );
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
