<?php
namespace Repeka\Application\Repository;

use Doctrine\ORM\EntityRepository;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\Exception\EntityNotFoundException;
use Repeka\Domain\Repository\ResourceKindRepository;

class ResourceKindDoctrineRepository extends EntityRepository implements ResourceKindRepository {
    public function save(ResourceKind $resourceKind): ResourceKind {
        $this->getEntityManager()->persist($resourceKind);
        return $resourceKind;
    }

    public function findOne(int $id): ResourceKind {
        /** @var ResourceKind $resourceKind */
        $resourceKind = $this->find($id);
        if (!$resourceKind) {
            throw new EntityNotFoundException($this, $id);
        }
        return $resourceKind;
    }

    public function exists(int $id): bool {
        return !!$this->find($id);
    }
}
