<?php

namespace Bundle\Site;

use Bolt\Events\QueryEvents;
use Bolt\Events\StorageEvents;
use Bolt\Extension\SimpleExtension;
use Bundle\Site\Listeners\StorageEventsListener;
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

    public function getServiceProviders()
    {
        return [
            $this,
            new Providers\QueryServiceProvider(),
            new Providers\StorageServiceProvider(),
            new Providers\ConfigServiceProvider($this->getConfig()),
        ];
    }

    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        $storageListener = $this->getContainer()['translate.listeners.storage'];

        $queryListener = $this->getContainer()['translate.listeners.query'];

        $dispatcher->addListener(StorageEvents::POST_SAVE, [$storageListener, 'onRecordCreate']);
        $dispatcher->addListener(StorageEvents::PRE_SAVE, [$storageListener, 'onRecordModify']);
        $dispatcher->addListener(QueryEvents::PARSE, [$queryListener, 'onQueryParse']);
    }
}
