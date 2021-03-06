<?php
namespace Repeka\Domain\UseCase\Metadata;

use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Repository\MetadataRepository;

class MetadataListQueryHandler {
    /**
     * @var MetadataRepository
     */
    private $metadataRepository;

    public function __construct(MetadataRepository $metadataRepository) {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @param MetadataListQuery
     * @return Metadata[]
     */
    public function handle(MetadataListQuery $query): array {
        return $this->metadataRepository->findByQuery($query);
    }
}
