<?php

namespace Bundle\Site\Listeners;


use Bolt\Collection\Bag;
use Bolt\Events\StorageEvent;
use Bolt\Storage\EntityManager;
use Bolt\Storage\QuerySet;
use Bundle\Site\Config\Config;
use Bundle\Site\Config\LocaleResolver;
use Silex\Application;

class StorageEventsListener
{
    /**
     * @var EntityManager
     */
    private $manager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var LocaleResolver
     */
    private $resolver;
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->manager = $app['storage'];
        $this->config = $app['translate.config'];
        $this->resolver = new LocaleResolver();
        $this->app = $app;
    }

    public function onRecordCreate(StorageEvent $event)
    {
        if(!$event->isCreate()) {
            return;
        }

        $repo = $this->manager->getRepository($event->getContentType());

        $set = new QuerySet();

        $translatableFields = $this->config->getContentTypeTranslatableFields($event->getContentType());

        $values = Bag::from($event->getContent()->getValues())->filter(function($key, $value) use ($translatableFields) {
            return isset($translatableFields[$key]);
        });

        foreach ($this->config->getLocaleSlugs() as $slug) {
            $set->append(
                $this->createQuery($repo->getTableName() . "_$slug", $values, $event->getContent()->get('id'))
            );
        }

        $set->execute();
    }

    public function onRecordModify(StorageEvent $event)
    {
        if($event->isCreate()) {
            return;
        }

        /* Set the request object and fetch the locale */
        $this->resolver->setRequest($this->app['request']);
        $locale = $this->resolver->current();

        $repo = $this->manager->getRepository($event->getContentType());

        /* Get the new Content entity, and fetch the current Entity from the database */
        $content = $event->getContent();
        $oldContent = $repo->find($content->get('id'));

        /* Get the translation table name */
        $table = $repo->getTableName() . "_$locale";

        /* Figure out which fields are translatable */
        $translatableFields = $this->config->getContentTypeTranslatableFields($event->getContentType());

        /* Filter out all non translatable fields from the new Content entity */
        $values = Bag::from($content->getValues())->filter(function($key, $value) use ($translatableFields) {
            return isset($translatableFields[$key]);
        })->toArray();

        /* Update the record in the translation table */
        $qb = $this->editQuery($table, $values, $content->get('id'));

        $qb->execute();

        /* Refill the new Content entity with old values, so that we only modify non-translatable fields */
        foreach ($values as $key => $value) {
            $content[$key] = $oldContent[$key];
        }
    }

    private function createQuery($table, $values, $contentId) {
        $qb = $this->manager->createQueryBuilder()
            ->insert($table)
        ;

        $i = 0;

        foreach ($values as $key => $value) {
            $qb->setValue($key, '?');
            $qb->setParameter($i, $value);

            $i++;
        }

        $qb->setValue('content_id', ':content_id')
            ->setParameter('content_id', $contentId)
        ;

        return $qb;
    }

    private function editQuery($table, $values, $contentId) {
        $qb = $this->manager->createQueryBuilder()
            ->update($table)
            ->where('content_id = :content_id')
            ->setParameter('content_id', $contentId)
        ;

        foreach ($values as $key => $value) {
            $qb->set($key, ":$key");
            $qb->setParameter($key, $value);
        }

        return $qb;
    }
}