<?php

namespace Bundle\Site\Listeners;


use Bolt\Events\QueryEvent;
use Bolt\Storage\Query\ContentQueryParser;
use Bundle\Site\Config\LocaleResolver;

class QueryEventsListener
{
    /**
     * @var LocaleResolver
     */
    private $resolver;

    public function __construct($app)
    {
        $this->resolver = new LocaleResolver();
        $this->app = $app;
    }

    public function onQueryParse(QueryEvent $event)
    {
        /** @var ContentQueryParser $parser */
        $parser = $event->getQuery();

        $this->resolver->setRequest($this->app['request']);

        $parser->setDirective('translate', $this->resolver->current());
    }
}