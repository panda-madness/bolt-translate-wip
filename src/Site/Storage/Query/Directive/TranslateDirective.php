<?php

namespace Bundle\Site\Storage\Query\Directive;


use Bolt\Storage\Query\QueryInterface;

class TranslateDirective
{
    /**
     * @param QueryInterface $query
     * @param string $locale
     */
    public function __invoke(QueryInterface $query, $locale)
    {
        $qb = $query->getQueryBuilder();

        $originalAlias = $qb->getQueryPart('from')[0]['alias'];
        $originalTable = $qb->getQueryPart('from')[0]['table'];

        $translateTable = $originalTable . "_$locale";

        $qb = $qb->select("$originalAlias.*, t.*, $originalAlias.id");

        $qb->join($originalAlias, $translateTable, 't', "t.content_id = $originalAlias.id");
    }
}