<?php

namespace Bundle\Site\Config;


use Symfony\Component\HttpFoundation\Request;

class LocaleResolver
{
    /**
     * @var Request|null
     */
    private $request = null;

    public function __construct(Request $request = null)
    {
        $this->request = $request;
    }


    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function current()
    {
        return $this->request->query->get('_locale', 'ru');
    }
}