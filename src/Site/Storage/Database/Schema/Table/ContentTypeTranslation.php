<?php

namespace Bundle\Site\Storage\Database\Schema\Table;


use Bolt\Storage\Database\Schema\Table\ContentType;

class ContentTypeTranslation extends ContentType
{
    /**
     * @var string ContentType Slug
     */
    protected $contentType;

    /**
     * Add columns to the table.
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('content_id', 'integer', []);
    }

    /**
     * Define the columns that require indexing.
     */
    protected function addIndexes() {}

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getContentType()
    {
        return $this->contentType;
    }
}