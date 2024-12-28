<?php

namespace Nuts\Search\Console;

use Laravel\Scout\Console\IndexCommand as BaseIndexCommand;
use Illuminate\Console\Command;
use Laravel\Scout\Contracts\UpdatesIndexSettings;
use Nuts\Search\Classes\EngineManager;
use Illuminate\Support\Str;

class IndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:index
            {name : The name of the index}
            {--k|key= : The name of the primary key}';

    public function handle(EngineManager $manager)
    {
        $engine = $manager->engine();

        try {
            $options = [];

            if ($this->option('key')) {
                $options = ['primaryKey' => $this->option('key')];
            }

            if (class_exists($modelName = $this->argument('name'))) {
                $model = new $modelName;
            }


            $name = $this->indexName($this->argument('name'));

            $engine->createIndex($name, $options);

            if (method_exists($engine, 'updateIndexSettings')) {
                $driver = config('search.driver');

                $class = isset($model) ? get_class($model) : null;

                $settings = config('search.'.$driver.'.index-settings.'.$name)
                                ?? config('search.'.$driver.'.index-settings.'.$class)
                                ?? [];

                if (isset($model) &&
                    config('search.soft_delete', false) &&
                    in_array(SoftDeletes::class, class_uses_recursive($model))) {
                    $settings = $engine->configureSoftDeleteFilter($settings);
                }

                if ($settings) {
                    $engine->updateIndexSettings($name, $settings);
                }
            }

            $this->info('Index ["'.$name.'"] created successfully.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    protected function indexName($name)
    {
        if (class_exists($name)) {
            return (new $name)->indexableAs();
        }

        $prefix = config('search.prefix');

        return ! Str::startsWith($name, $prefix) ? $prefix.$name : $name;
    }
}
