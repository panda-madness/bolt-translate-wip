<?php

namespace Bundle\Site;

use Bolt\Collection\Bag;
use Bolt\Config;
use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\SimpleExtension;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Repository\ContentRepository;
use Bundle\Site\Storage\Database\Schema\Builder\ContentTranslationTables;
use Bundle\Site\Storage\Database\Schema\Manager;
use Bundle\Site\Storage\Database\Schema\Table\ContentTypeTranslation;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Site bundle extension loader.
 *
 * This is the base bundle you can use to further customise Bolt for your
 * specific site.
 *
 * It is perfectly safe to remove this bundle, just remember to remove the
 * entry from your .bolt.yml or .bolt.php file.
 *
 * For more information on building bundles see https://docs.bolt.cm/extensions
 */
class CustomisationExtension extends SimpleExtension
{
    protected function getDefaultConfig()
    {
        return [
            'locales' => [
                'ru_RU' => [
                    'slug' => 'ru'
                ],
                'en_US' => [
                    'slug' => 'en'
                ],
                'kz_KZ' => [
                    'slug' => 'kz'
                ],
                'de_DE' => [
                    'slug' => 'de'
                ],
            ],
        ];
    }

    protected function registerServices(Application $app)
    {
        $app['schema'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );

        $app['schema.builder'] = $app->extend('schema.builder', function($builder) use($app) {
            $builder['content_translations'] = $app->share(
                function () use ($app) {
                    $slugs = $this->getLocaleSlugs();

                    $builder = new ContentTranslationTables(
                        $app['db'],
                        $app['schema'],
                        $app['schema.content_translation_tables'],
                        $app['schema.charset'],
                        $app['schema.collate'],
                        $app['logger.system'],
                        $app['logger.flash']
                    );

                    $builder->setLocaleSlugs($slugs);

                    return $builder;
                }
            );

            return $builder;
        });

        $app['schema.content_translation_tables'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();
                $prefix = $app['schema.prefix'];

                $contentTypes = $app['config']->get('contenttypes');
                $locales = $this->getLocaleSlugs();
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

//        $app['schema'] = $app->share(
//            function ($app) {
//                return new LocalizedManager($app);
//            }
//        );

//        $app['storage.repository.default'] = LocalizedContentRepository::class;

//        $app['storage.content_repository'] = $app->protect(
//            function (ClassMetadata $classMetadata) use ($app) {
//                $repoClass = $app['storage.repository.default'];
//
//                $classMetadata->setName(LocalizedContent::class);
//
//                /** @var LocalizedContentRepository $repo */
//                $repo = new $repoClass($app['storage'], $classMetadata);
//
//                return $repo;
//            }
//        );
    }

    public function boot(Application $app)
    {
        parent::boot($app);
    }

    private function getLocaleSlugs() {
        $bag = Bag::from($this->getConfig()['locales']);

        return $bag->map(function($locale, $config) {
            return $config['slug'];
        });
    }

    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
//        $dispatcher->addListener(StorageEvents::POST_SAVE, [$this, 'onRecordSave']);
//        $dispatcher->addListener(StorageEvents::PRE_SAVE, [$this, 'onRecordModify']);
//        $dispatcher->addListener('preMassHydrate', [$this, 'onPreMassHydrate']);
    }

    public function onRecordSave(StorageEvent $event)
    {
        if(!$event->isCreate()) {
            return;
        }

        /** @var LocalizedContent $content */
        $content = $event->getContent();

        $contentType = $event->getContentType();

        /** @var EntityManager $storage */
        $storage = $this->getContainer()['storage'];

        /** @var LocalizedContentRepository $repo */
        $repo = $storage->getRepository($contentType);

        /** @var Config $config */
        $config = $this->getContainer()['config'];

        $fields = Bag::from($config->get("contenttypes/$contentType/fields"))->filter(function($field, $options) {
            return isset($options['translatable']) && $options['translatable'] === true;
        });
    }

    public function onRecordModify(StorageEvent $event)
    {
        if($event->isCreate()) {
            return;
        }
    }

    public function onPreMassHydrate(HydrationEvent $event)
    {
        $repo = $event->getArgument('repository');

        if(!$repo instanceof LocalizedContentRepository) {
            return;
        }

        $table = $repo->getTableName() . '_ru';

        /** @var EntityManager $storage */
        $storage = $this->getContainer()['storage'];

        $qb = $storage->createQueryBuilder();

        $data = Bag::from($event->getSubject());

        $ids = $data->map(function ($key, $value) { return $value['id']; });

        $qb->select('*')->from($table)->where(
            $qb->expr()->in('content_id', ':ids')
        )->setParameter('ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

//        dump($qb->execute()->fetchAll());
//        die();
    }
}
