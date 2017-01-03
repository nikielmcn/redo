<?php
namespace Repeka\Application\Repository;

use Doctrine\ORM\EntityRepository;
use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Repository\ResourceRepository;

class ResourceDoctrineRepository extends EntityRepository implements ResourceRepository {
    public function save(ResourceEntity $resource): ResourceEntity {
        $this->getEntityManager()->persist($resource);
        return $resource;
    }
}