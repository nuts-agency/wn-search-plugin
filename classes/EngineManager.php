<?php

namespace Nuts\Search\Classes;

use Algolia\AlgoliaSearch\Config\SearchConfig;
use Algolia\AlgoliaSearch\SearchClient as Algolia;
use Algolia\AlgoliaSearch\Support\UserAgent;
use Laravel\Scout\EngineManager as BaseEngineManager;
use Meilisearch\Client as Meilisearch;
use Nuts\Search\Engines\AlgoliaEngine;
use Nuts\Search\Engines\CollectionEngine;
use Nuts\Search\Engines\DatabaseEngine;
use Nuts\Search\Engines\MeilisearchEngine;
use Nuts\Search\Engines\NullEngine;

/**
 * Engine Manager wrapper.
 *
 * This provides compatibility with our configuration, and uses our own Engine classes.
 */
class EngineManager extends BaseEngineManager
{
    /**
     * Create an Algolia engine instance.
     *
     * @return \Winter\Search\Engines\AlgoliaEngine
     */
    public function createAlgoliaDriver()
    {
        $this->ensureAlgoliaClientIsInstalled();

        UserAgent::addCustomUserAgent('Winter Search', '1.0.0');

        $config = SearchConfig::create(
            config('search.algolia.id'),
            config('search.algolia.secret')
        )->setDefaultHeaders(
            $this->defaultAlgoliaHeaders()
        );

        if (is_int($connectTimeout = config('search.algolia.connect_timeout'))) {
            $config->setConnectTimeout($connectTimeout);
        }

        if (is_int($readTimeout = config('search.algolia.read_timeout'))) {
            $config->setReadTimeout($readTimeout);
        }

        if (is_int($writeTimeout = config('search.algolia.write_timeout'))) {
            $config->setWriteTimeout($writeTimeout);
        }

        return new AlgoliaEngine(Algolia::createWithConfig($config), config('search.soft_delete'));
    }

    /**
     * Set the default Algolia configuration headers.
     *
     * @return array
     */
    protected function defaultAlgoliaHeaders()
    {
        if (!config('search.identify')) {
            return [];
        }

        $headers = [];

        if (
            !config('app.debug') &&
            filter_var($ip = request()->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            $headers['X-Forwarded-For'] = $ip;
        }

        if (($user = request()->user()) && method_exists($user, 'getKey')) {
            $headers['X-Algolia-UserToken'] = $user->getKey();
        }

        return $headers;
    }

    /**
     * Create an Meilisearch engine instance.
     *
     * @return \Winter\Search\Engines\MeilisearchEngine
     */
    public function createMeilisearchDriver()
    {
        $this->ensureMeilisearchClientIsInstalled();

        return new MeilisearchEngine(
            $this->container->make(Meilisearch::class),
            config('search.soft_delete', false)
        );
    }

    /**
     * Create a database engine instance.
     *
     * @return \Winter\Search\Engines\DatabaseEngine
     */
    public function createDatabaseDriver()
    {
        return new DatabaseEngine;
    }

    /**
     * Create a collection engine instance.
     *
     * @return \Winter\Search\Engines\CollectionEngine
     */
    public function createCollectionDriver()
    {
        return new CollectionEngine;
    }

    /**
     * Create a null engine instance.
     *
     * @return \Winter\Search\Engines\NullEngine
     */
    public function createNullDriver()
    {
        return new NullEngine;
    }

    /**
     * Get the default Winter Search driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        if (is_null($driver = config('search.driver'))) {
            return 'null';
        }

        return $driver;
    }
}
