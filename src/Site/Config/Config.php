<?php

namespace Bundle\Site\Config;


use Bolt\Collection\Bag;
use Bolt\Config as BoltConfig;

class Config
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var BoltConfig
     */
    protected $boltConfig;

    public function __construct($config, BoltConfig $boltConfig)
    {
        $this->config = $config;
        $this->boltConfig = $boltConfig;
    }

    public function getLocaleSlugs()
    {
        $bag = Bag::from($this->config['locales']);

        return $bag->map(function($locale, $config) {
            return $config['slug'];
        })->toArray();
    }

    public function getContentTypeTranslatableFields($contentType)
    {
        return Bag::from($this->boltConfig->get("contenttypes/$contentType/fields"))->filter(function($field, $options) {
            return isset($options['translatable']) && $options['translatable'] === true;
        })->toArray();
    }
}