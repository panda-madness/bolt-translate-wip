<?php

namespace Bundle\Site\Providers;


use Bolt\Storage\Query\ContentQueryParser;
use Bundle\Site\Storage\Query\Directive\TranslateDirective;
use Silex\Application;
use Silex\ServiceProviderInterface;

class QueryServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['query.parser'] = $app->extend(
            'query.parser',
            function (ContentQueryParser $parser) {
                $parser->addDirectiveHandler('translate', new TranslateDirective());

                return $parser;
            }
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {

    }
}