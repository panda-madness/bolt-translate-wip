<?php

namespace Bundle\Site;


use Bolt\Storage\Entity\Content;

class LocalizedContent extends Content
{
    protected $content_id;

    public function setContent_id($id)
    {
        $this->content_id = $id;
    }

    public function getContent_id()
    {
        return $this->content_id;
    }
}