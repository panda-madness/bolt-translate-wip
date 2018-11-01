<?php

namespace Bundle\Site\Providers;


use Bundle\Site\Config\Config;
use Bundle\Site\Config\LocaleResolver;
use Bundle\Site\Listeners\QueryEventsListener;
use Bundle\Site\Listeners\StorageEventsListener;
use Bundle\Site\Storage\Database\Schema\Builder\ContentTranslationTables;
use Bundle\Site\Storage\Database\Schema\Manager;
use Bundle\Site\Storage\Database\Schema\Table\ContentTypeTranslation;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function __construct()
    {

    }

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['schema'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );

        $app['schema.builder'] = $app->extend('schema.builder', function($builder) use($app) {
            $builder['content_translations'] = $app->share(
                function () use ($app) {
                    /** @var Config $config */
                    $config = $app['translate.config'];

                    $builder = new ContentTranslationTables(
                        $app['db'],
                        $app['schema'],
                        $app['schema.content_translation_tables'],
                        $app['schema.charset'],
                        $app['schema.collate'],
                        $app['logger.system'],
                        $app['logger.flash']
                    );

                    $builder->setLocaleSlugs($config->getLocaleSlugs());

                    return $builder;
                }
            );

            return $builder;
        });

        $app['schema.content_translation_tables'] = $app->share(
            function (Application $app) {
                /** @var Config $config */
                $config = $app['translate.config'];

                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();
                $prefix = $app['schema.prefix'];

                $contentTypes = $app['config']->get('contenttypes');
                $locales = $config->getLocaleSlugs();
                $acne = new \Pimple();

                foreach (array_keys($contentTypes) as $contentType) {
                    foreach ($locales as $locale) {
                        $tableName = $contentTypes[$contentType]['tablename'] . "_$locale";
                        $acne[$tableName] = $app->share(
                            function () use ($platform, $prefix, $contentType) {
                                $table = new ContentTypeTranslation($platform, $prefix);
                                $table->setContentType($contentType);

                                return $table;
                            }
                        );
                    }
                }

                return $acne;
            }
        );

        $app['schema.tables'] = $app->extend('schema.tables', function($acne) use($app) {
            foreach ($app['schema.content_translation_tables']->keys() as $baseName) {
                $acne[$baseName] = $app->share(
                    function () use ($app, $baseName) {
                        return $app['schema.content_translation_tables'][$baseName];
                    }
                );
            }

            return $acne;
        });

        $app['translate.listeners.storage'] = $app::share(function($app) {
            return new StorageEventsListener(
                $app
            );
        });

        $app['translate.listeners.query'] = $app::share(function($app) {
            return new QueryEventsListener($app);
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

    }
}