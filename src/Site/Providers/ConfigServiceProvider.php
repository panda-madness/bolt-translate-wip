<?php

namespace Bundle\Site\Providers;


use Bundle\Site\Config\Config;
use Bundle\Site\Config\LocaleResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['translate.config'] = $app::share(function($app) {
            return new Config($this->config, $app['config']);
        });

        $app['translate.config.locale_resolver'] = $app::share(function($app) {
            return new LocaleResolver($app['request']);
        });
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
//        $app['translate.config.locale_resolver']->setRequest($app['request']);
    }
}