<?php
namespace Repeka\Tests\Application\Elasticsearch\Model;

use Elastica\Document;
use Elastica\Type;
use Repeka\Application\Elasticsearch\Mapping\ResourceConstants;
use Repeka\Application\Elasticsearch\Model\ElasticSearch;
use Repeka\Application\Elasticsearch\Model\ESContentsAdjuster;
use Repeka\Domain\Entity\MetadataControl;
use Repeka\Tests\Application\Elasticsearch\ElasticsearchTest;
use Repeka\Tests\Traits\StubsTrait;

class ESResourceTest extends ElasticsearchTest {
    use StubsTrait;

    public function setUp() {
        parent::setUp();
    }

    public function testInsertingDocument() {
        $resourceKind = $this->createResourceKindMock(2);
        $resource = $this->createResourceMock(1, $resourceKind, ['3' => ['value' => 'aaa']]);
        $typeMock = $this->createMock(Type::class);
        $typeMock->expects($this->once())->method('getIndex')->willReturn($this->indexMock);
        $this->indexMock->expects($this->once())->method('refresh');
        $typeMock->expects($this->once())->method('addDocument')->with(
            $this->callback(
                function ($doc) {
                    /** @var $doc Document */
                    $this->assertEquals(
                        [
                            ResourceConstants::ID => 1,
                            ResourceConstants::KIND_ID => 2,
                            ResourceConstants::CONTENTS => ['3' => [['value_text' => 'aaa']]],
                            ResourceConstants::RESOURCE_CLASS => 'books',
                        ],
                        $doc->getData()
                    );
                    return true;
                }
            )
        );
        $metadataRepository = $this->createMetadataRepositoryStub(
            [
                $this->createMetadataMock(3, null, MetadataControl::TEXT(), [], 'books', [], 'TEXT'),
            ]
        );
        $esContentsAdjuster = new ESContentsAdjuster($metadataRepository);
        $this->indexMock->method('getType')->with(ResourceConstants::ES_DOCUMENT_TYPE)->willReturn($typeMock);
        $res = new ElasticSearch($this->clientMock, $esContentsAdjuster, self::INDEX_NAME);
        $res->insertDocument($resource);
    }
}
