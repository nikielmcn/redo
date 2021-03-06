<?php
namespace Repeka\Application\Elasticsearch\Model;

use Elastica\Document;
use Elastica\Index;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\Type;
use Repeka\Application\Elasticsearch\ESClient;
use Repeka\Application\Elasticsearch\Mapping\FtsConstants;
use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Repository\ResourceFtsProvider;
use Repeka\Domain\UseCase\Resource\ResourceListFtsQuery;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class ElasticSearch implements ResourceFtsProvider {
    /** @var ESClient */
    private $client;

    /** @var Index */
    private $index;

    /** @var Type */
    private $type;

    /** @var ESContentsAdjuster */
    private $esContentsAdjuster;

    public function __construct(ESClient $client, ESContentsAdjuster $esContentsAdjuster, string $indexName) {
        $this->client = $client;
        $this->esContentsAdjuster = $esContentsAdjuster;
        $this->index = $this->client->getIndex($indexName);
        $this->type = $this->index->getType(FtsConstants::ES_DOCUMENT_TYPE);
    }

    private function createDocument(ResourceEntity $resource): Document {
        $data = [
            'id' => $resource->getId(),
            'kindId' => $resource->getKind()->getId(),
            'contents' => $this->esContentsAdjuster->adjustContents($resource, $resource->getContents()->toArray()),
            'resourceClass' => $resource->getResourceClass(),
        ];
        return new Document($resource->getId(), $data);
    }

    public function insertDocument(ResourceEntity $resource) {
        $document = $this->createDocument($resource);
        $this->type->addDocument($document);
        $this->type->getIndex()->refresh();
    }

    public function deleteDocument(int $resourceId) {
        $this->type->deleteById(strval($resourceId));
        $this->type->getIndex()->refresh();
    }

    /** @param ResourceEntity[] $resources */
    public function insertDocuments(iterable $resources) {
        $documents = [];
        foreach ($resources as $resource) {
            $documents[] = $this->createDocument($resource);
        }
        $this->type->addDocuments($documents);
        $this->type->getIndex()->refresh();
    }

    /** @return ResultSet */
    public function search(ResourceListFtsQuery $query) {
        $esQuery = new ElasticSearchQuery($query);
        $search = new Search($this->client);
        $search->addIndex($this->index->getName());
        $search->addType(FtsConstants::ES_DOCUMENT_TYPE);
        $search->setQuery($esQuery->getQuery());
        return $search->search();
    }

    public function index(ResourceEntity $resource): void {
        $this->insertDocument($resource);
    }

    public function delete(int $resourceId): void {
        $this->deleteDocument($resourceId);
    }
}
