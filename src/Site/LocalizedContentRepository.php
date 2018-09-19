<?php

namespace Bundle\Site;


use ArrayObject;
use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\Repository\ContentRepository;
use Doctrine\DBAL\Query\QueryBuilder;

class LocalizedContentRepository extends ContentRepository
{
    /**
     * Internal method to hydrate an Entity Object from fetched data.
     *
     * @param array        $data
     * @param QueryBuilder $qb
     *
     * @return mixed
     */
    protected function hydrate(array $data, QueryBuilder $qb)
    {
        $entity = $this->getEntityBuilder()->getEntity();

        $data = new ArrayObject($data);
        $preEventArgs = new HydrationEvent($data, ['entity' => $entity, 'repository' => $this]);
        $this->event()->dispatch(StorageEvents::PRE_HYDRATE, $preEventArgs);

        $this->getEntityBuilder()->createFromDatabaseValues($data, $entity);

        $postEventArgs = new HydrationEvent($entity, ['entity' => $entity, 'data' => $data, 'repository' => $this]);
        $this->event()->dispatch(StorageEvents::POST_HYDRATE, $postEventArgs);

        return $entity;
    }

    /**
     * Internal method to hydrate an array of Entity Objects from fetched data.
     *
     * @param array        $data
     * @param QueryBuilder $qb
     *
     * @return mixed
     */
    protected function hydrateAll(array $data, QueryBuilder $qb)
    {
        $preEventArgs = new HydrationEvent($data, ['repository' => $this]);
        $this->event()->dispatch('preMassHydrate', $preEventArgs);

        $data = new ArrayObject($data);

        $rows = [];
        foreach ($data as $row) {
            $rows[] = $this->hydrate($row, $qb);
        }

        $postEventArgs = new HydrationEvent($rows, [ 'data' => $data, 'repository' => $this]);
        $this->event()->dispatch('postMassHydrate', $postEventArgs);

        return $rows;
    }
}