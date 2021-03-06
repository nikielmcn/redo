<?php
namespace Repeka\Domain\UseCase\Resource;

use Repeka\Domain\Cqrs\Command;
use Repeka\Domain\Cqrs\CommandAdjuster;
use Repeka\Domain\Entity\ResourceContents;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\Repository\MetadataRepository;
use Repeka\Domain\Repository\ResourceKindRepository;
use Repeka\Domain\UseCase\ColumnSortDataConverter;

class ResourceListQueryAdjuster implements CommandAdjuster {

    /** @var MetadataRepository */
    private $metadataRepository;
    /** @var ResourceKindRepository */
    private $resourceKindRepository;
    /** @var ColumnSortDataConverter */
    private $columnSortDataConverter;

    public function __construct(
        MetadataRepository $metadataRepository,
        ResourceKindRepository $resourceKindRepository,
        ColumnSortDataConverter $columnSortDataConverter
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->resourceKindRepository = $resourceKindRepository;
        $this->columnSortDataConverter = $columnSortDataConverter;
    }

    /**
     * @param ResourceListQuery $query
     * @return ResourceListQuery
     */
    public function adjustCommand(Command $query): Command {
        return ResourceListQuery::withParams(
            $query->getIds(),
            $query->getResourceClasses(),
            $this->convertResourceKindIdsToResourceKinds($query->getResourceKinds()),
            $this->columnSortDataConverter->convertSortByMetadataColumnsToIntegers($query->getSortBy()),
            $query->getParentId(),
            $this->mapMetadataNamesToIdsInContentFilter($query->getContentsFilters()),
            $query->onlyTopLevel(),
            $query->getPage(),
            $query->getResultsPerPage(),
            $query->getWorkflowPlacesIds()
        );
    }

    /** @retrun ResourceKind[] */
    private function convertResourceKindIdsToResourceKinds(array $resourceKindIds): array {
        return array_map(
            function ($resourceKindOrId) {
                return $resourceKindOrId instanceof ResourceKind
                    ? $resourceKindOrId
                    : $this->resourceKindRepository->findOne($resourceKindOrId);
            },
            $resourceKindIds
        );
    }

    private function mapMetadataNamesToIdsInContentFilter(array $contentsFilters) {
        return array_map(
            function (ResourceContents $contentsFilter) {
                return $contentsFilter->withMetadataNamesMappedToIds($this->metadataRepository);
            },
            $contentsFilters
        );
    }
}
