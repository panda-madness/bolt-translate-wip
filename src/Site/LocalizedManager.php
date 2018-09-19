<?php

namespace Bundle\Site;


use Bolt\Storage\Database\Schema\Manager;
use Doctrine\DBAL\Schema\Schema;
use Silex\Application;

class LocalizedManager extends Manager
{
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function getSchemaTables()
    {
        if ($this->schemaTables !== null) {
            return $this->schemaTables;
        }

        $builder = $this->app['schema.builder'];

        /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
        $builder['extensions']->addPrefix($this->app['schema.prefix']);

        $schema = new Schema();

        $tables = [];

        foreach ($builder as $item) {
            $tables = array_merge($tables, $item->getSchemaTables($schema, $this->config));
        }

        $this->schema = $schema;

        return $this->schemaTables = $tables;
    }

    /**
     * @return \Bolt\Storage\Database\Schema\Timer
     */
    private function getSchemaTimer()
    {
        return $this->app['schema.timer'];
    }

    /**
     * @return \Bolt\Storage\Database\Schema\Comparison\BaseComparator
     */
    private function getSchemaComparator()
    {
        return $this->app['schema.comparator'];
    }
}