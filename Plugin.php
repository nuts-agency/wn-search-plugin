<?php namespace Nuts\Search;

use Config;
use System\Classes\PluginBase;
use Nuts\Search\Classes\EngineManager;

use Nuts\Search\Console\FlushCommand;
use Nuts\Search\Console\IndexCommand;
use Nuts\Search\Console\ImportCommand;
use Nuts\Search\Console\DeleteIndexCommand;
use Nuts\Search\Console\DeleteAllIndexesCommand;
use Nuts\Search\Console\SyncIndexSettingsCommand;

class Plugin extends PluginBase
{

    public $require = ['Nuts.Meilisearch'];

    public function register()
    {
        $this->app->singleton(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });
    }

    public function boot()
    {
        Config::set('search', Config::get('nuts.search::search'));

        $this->registerCommands();

        if ($this->app->runningInConsole()) {
            $this->registerPublishedConfig();
        }
    }

    protected function registerCommands()
    {
        $this->commands([
            SyncIndexSettingsCommand::class,
            DeleteIndexCommand::class,
            FlushCommand::class,
            ImportCommand::class,
            IndexCommand::class,
            DeleteAllIndexesCommand::class,
        ]);
    }

    protected function registerPublishedConfig()
    {
        $this->publishes([
            __DIR__ . '/config/search.php' => implode(DIRECTORY_SEPARATOR, [
                $this->app->configPath(),
                'nuts',
                'search',
                'search.php'
            ])
        ]);
    }
}
