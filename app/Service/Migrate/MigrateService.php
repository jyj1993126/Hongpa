<?php
namespace App\Service\Migrate;

use App\Kernel\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;

/**
 * @author Leon J
 * @since 2017/5/10
 */
class MigrateService implements \App\Contract\Service
{
    /**
     * @var Migrator
     */
    private $migrator;
    
    /**
     * MigrateService constructor.
     */
    public function __construct()
    {
        $capsule = new Capsule;
        foreach (config('database.mysql') as $name => $config) {
            $capsule->addConnection($config, $name);
        }
        $capsule->setAsGlobal();
        $connectionResolver = $capsule->getDatabaseManager();
        $repository = new DatabaseMigrationRepository($connectionResolver, 'migrations');
        
        if (!$repository->repositoryExists()) {
            $repository->createRepository();
        }
        
        $this->migrator = new Migrator($repository, $connectionResolver, app('filesystem'));
    }
    
    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
        //migrate
        $migrate = new MigrateCommand($this->migrator);
        $migrate->setLaravel($app);
        console()->add($migrate);
        
        //migrateMake
        $migrateMake = new MigrateMakeCommand(new CustomStubCreator(app('filesystem')), app('composer'));
        $migrateMake->setLaravel($app);
        console()->add($migrateMake);
        
        //reset
        $migrateReset = new ResetCommand($this->migrator);
        $migrateReset->setLaravel($app);
        console()->add($migrateReset);
        
        //rollback
        $migrateRollback = new RollbackCommand($this->migrator);
        $migrateRollback->setLaravel($app);
        console()->add($migrateRollback);
        
        //status
        $migrateStatus = new StatusCommand($this->migrator);
        $migrateStatus->setLaravel($app);
        console()->add($migrateStatus);
        
        //refresh
        $migrateRefresh = new RefreshCommand($this->migrator);
        $migrateRefresh->setLaravel($app);
        console()->add($migrateRefresh);
    }
}
