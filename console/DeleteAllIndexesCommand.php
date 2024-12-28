<?php

namespace Nuts\Search\Console;

use Illuminate\Console\Command;
use Nuts\Search\Classes\EngineManager;

class DeleteAllIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:delete-all-indexes';
    protected $description = 'Delete all indexes';

    public function handle(EngineManager $manager)
    {
        $engine = $manager->engine();

        $driver = config('search.driver');

        if (! method_exists($engine, 'deleteAllIndexes')) {
            return $this->error('The ['.$driver.'] engine does not support deleting all indexes.');
        }

        try {
            $manager->engine()->deleteAllIndexes();

            $this->info('All indexes deleted successfully.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
