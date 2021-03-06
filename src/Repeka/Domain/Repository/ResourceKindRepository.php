<?php
namespace Repeka\Domain\Repository;

use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Exception\EntityNotFoundException;
use Repeka\Domain\UseCase\PageResult;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindListQuery;

interface ResourceKindRepository {
    /** @return ResourceKind[] */
    public function findAll();

    /** @return ResourceKind[] */
    public function findAllSystemResourceKinds(): array;

    /**
     * @throws EntityNotFoundException if the entity could not be found
     */
    public function findOne(int $id): ResourceKind;

    public function save(ResourceKind $resourceKind): ResourceKind;

    public function exists(int $id): bool;

    public function delete(ResourceKind $resourceKind): void;

    public function countByResourceWorkflow(ResourceWorkflow $resourceWorkflow): int;

    public function countByMetadata(Metadata $metadata): int;

    /** @return ResourceKind[] */
    public function findByQuery(ResourceKindListQuery $query): array;

    public function removeEveryResourceKindsUsageInOtherResourceKinds(ResourceKind $resourceKind): void;
}
