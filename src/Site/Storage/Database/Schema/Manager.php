<?php

namespace Bundle\Site\Storage\Database\Schema;


use Bolt\Storage\Database\Schema\Manager as BaseManager;
use Doctrine\DBAL\Schema\Schema;
use Silex\Application;

class Manager extends BaseManager
{
    /** @var \Silex\Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        parent::__construct($app);
    }

    /**
     * Get a merged array of tables.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getSchemaTables()
    {
        if ($this->schemaTables !== null) {
            return $this->schemaTables;
        }
        $builder = $this->app['schema.builder'];

        /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
        $builder['extensions']->addPrefix($this->app['schema.prefix']);

        $schema = new Schema();
        $tables = array_merge(
            $builder['base']->getSchemaTables($schema),
            $builder['content']->getSchemaTables($schema, $this->config),
            $builder['content_translations']->getSchemaTables($schema, $this->config),
            $builder['extensions']->getSchemaTables($schema)
        );
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