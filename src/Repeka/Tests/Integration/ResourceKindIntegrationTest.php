<?php
namespace Repeka\Tests\Integration;

use Repeka\Domain\Constants\SystemMetadata;
use Repeka\Domain\Constants\SystemResourceKind;
use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Entity\MetadataControl;
use Repeka\Domain\Entity\ResourceContents;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowPlace;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowTransition;
use Repeka\Domain\Repository\MetadataRepository;
use Repeka\Domain\Repository\ResourceKindRepository;
use Repeka\Domain\Repository\ResourceRepository;
use Repeka\Domain\UseCase\Resource\ResourceCreateCommand;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindListQuery;
use Repeka\Tests\IntegrationTestCase;

class ResourceKindIntegrationTest extends IntegrationTestCase {
    const ENDPOINT = '/api/resource-kinds';
    const AUTO_CREATED_METADATA_COUNT = 3;

    /** @var ResourceKind */
    private $resourceKind;
    /** @var Metadata */
    private $metadata1;
    /** @var Metadata */
    private $metadata2;
    /** @var  MetadataRepository */
    private $metadataRepository;
    /** @var ResourceKind */
    private $resourceKind2;

    public function setUp() {
        parent::setUp();
        $this->clearDefaultLanguages();
        $this->createLanguage('PL', 'PL', 'polski'); //for validate parentMetadata
        $this->createLanguage('EN', 'EN', 'angielski'); //for validate parentMetadata
        $this->metadataRepository = $this->container->get(MetadataRepository::class);
        $parentMetadata = $this->metadataRepository->findOne(SystemMetadata::PARENT);
        $baseMetadata1 = $this->createMetadata(
            'Metadata 1',
            ['PL' => 'Base metadata kind 1', 'EN' => 'Base metadata kind 1'],
            [],
            [],
            MetadataControl::TEXTAREA()
        );
        $baseMetadata2 = $this->createMetadata(
            'Metadata 2',
            ['PL' => 'Base metadata kind 2', 'EN' => 'Base metadata kind 2'],
            [],
            [],
            MetadataControl::TEXTAREA()
        );
        $baseDictionaryMetadata = $this->createMetadata(
            'Metadata 3',
            ['PL' => 'Base metadata dictionary', 'EN' => 'Base metadata dictionary'],
            [],
            [],
            MetadataControl::TEXTAREA,
            'dictionaries'
        );
        $this->resourceKind = $this->createResourceKind(
            ['PL' => 'Test', 'EN' => 'An awesome Test'],
            [$parentMetadata, $baseMetadata1, $baseMetadata2]
        );
        $this->resourceKind2 = $this->createResourceKind(['PL' => 'Test', 'EN' => 'Test'], [$parentMetadata, $baseDictionaryMetadata]);
        $this->metadata1 = $this->resourceKind->getMetadataList()[1];
        $this->metadata2 = $this->resourceKind->getMetadataList()[2];
    }

    public function testFetchingAllResourceKinds() {
        $client = self::createAdminClient();
        $client->apiRequest('GET', self::ENDPOINT, [], ['allResourceKinds' => true]);
        $this->assertStatusCode(200, $client->getResponse());
        $responseContent = json_decode($client->getResponse()->getContent());
        $this->assertCount(3, $responseContent);
        $responseItem = $responseContent[1];
        $this->assertEquals($this->resourceKind->getId(), $responseItem->id);
        $this->assertEquals($this->resourceKind->getLabel(), self::objectToArray($responseItem->label));
        $this->assertCount(self::AUTO_CREATED_METADATA_COUNT + 2, $responseItem->metadataList);
        $sortedMetadata = ($responseItem->metadataList[0]->id == $this->metadata1->getId())
            ? $responseItem->metadataList
            : array_reverse($responseItem->metadataList);
        $this->assertEquals($this->metadata1->getId(), $sortedMetadata[3]->id);
        $this->assertEquals($this->metadata2->getId(), $sortedMetadata[2]->id);
    }

    public function testFetchingAllResourceKindsOrderByIdAsc() {
        $client = self::createAdminClient();
        $client->apiRequest(
            'GET',
            self::ENDPOINT,
            [],
            [
                'allResourceKinds' => true,
                'sortByIds' => [['columnId' => 'id', 'direction' => 'ASC', 'language' => 'PL']],
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $fetchedIds = array_column(json_decode($client->getResponse()->getContent(), true), 'id');
        $this->assertCount(3, $fetchedIds);
        $this->assertEquals(
            [SystemResourceKind::USER, $this->resourceKind->getId(), $this->resourceKind2->getId()],
            $fetchedIds
        );
    }

    public function testFetchingAllResourceKindsOrderByLabelAscInPolish() {
        $client = self::createAdminClient();
        $client->apiRequest(
            'GET',
            self::ENDPOINT,
            [],
            [
                'allResourceKinds' => true,
                'sortByIds' => [['columnId' => 'label', 'direction' => 'ASC', 'language' => 'PL']],
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $fetchedIds = array_column(json_decode($client->getResponse()->getContent(), true), 'id');
        $this->assertCount(3, $fetchedIds);
        $this->assertEquals(
            [$this->resourceKind->getId(), $this->resourceKind2->getId(), SystemResourceKind::USER],
            $fetchedIds
        );
    }

    public function testFetchingAllResourceKindsOrderByLabelAscInEnglish() {
        $client = self::createAdminClient();
        $client->apiRequest(
            'GET',
            self::ENDPOINT,
            [],
            [
                'allResourceKinds' => true,
                'sortByIds' => [['columnId' => 'label', 'direction' => 'DESC', 'language' => 'EN']],
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $fetchedIds = array_column(json_decode($client->getResponse()->getContent(), true), 'id');
        $this->assertCount(3, $fetchedIds);
        $this->assertEquals(
            [SystemResourceKind::USER, $this->resourceKind2->getId(), $this->resourceKind->getId()],
            $fetchedIds
        );
    }

    public function testFetchingResourceKindsWithResourceClass() {
        $client = self::createAdminClient();
        $client->apiRequest('GET', self::ENDPOINT, [], ['resourceClasses' => ['books']]);
        $this->assertStatusCode(200, $client->getResponse());
        $responseContent = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $responseContent);
        $responseItem = $responseContent[0];
        $this->assertEquals($this->resourceKind->getId(), $responseItem->id);
        $this->assertEquals($this->resourceKind->getLabel(), self::objectToArray($responseItem->label));
        $this->assertCount(self::AUTO_CREATED_METADATA_COUNT + 2, $responseItem->metadataList);
        $sortedMetadata = ($responseItem->metadataList[0]->id == $this->metadata1->getId())
            ? $responseItem->metadataList
            : array_reverse($responseItem->metadataList);
        $this->assertEquals($this->metadata1->getId(), $sortedMetadata[3]->id);
        $this->assertEquals($this->metadata2->getId(), $sortedMetadata[2]->id);
    }

    public function testCreatingResourceKind() {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $metadata = $this->createMetadata(
            'New base',
            ['PL' => 'New base metadata', 'EN' => 'New base metadata'],
            [],
            [],
            MetadataControl::TEXTAREA()
        );
        $em->persist($metadata);
        $em->flush();
        $client = self::createAdminClient();
        $client->apiRequest(
            'POST',
            self::ENDPOINT,
            [
                'label' => ['PL' => 'created', 'EN' => 'created'],
                'metadataList' => [
                    ['id' => $metadata->getId(), 'label' => ['PL' => 'created overridden', 'EN' => 'created overridden']],
                ],
            ]
        );
        $this->assertStatusCode(201, $client->getResponse());
        $resourceKindRepository = self::createClient()->getContainer()->get(ResourceKindRepository::class);
        $createdId = json_decode($client->getResponse()->getContent())->id;
        /** @var ResourceKind $createdResourceKind */
        $createdResourceKind = $resourceKindRepository->findOne($createdId);
        $this->assertEquals(['PL' => 'created', 'EN' => 'created'], $createdResourceKind->getLabel());
        $this->assertCount(self::AUTO_CREATED_METADATA_COUNT + 1, $createdResourceKind->getMetadataList());
        $assignedMetadata = $createdResourceKind->getMetadataById($metadata->getId());
        $this->assertEquals($metadata->getControl(), $assignedMetadata->getControl());
        $this->assertEquals($metadata->getId(), $assignedMetadata->getId());
        $this->assertEquals($metadata->getName(), $assignedMetadata->getName());
        $this->assertEquals(['PL' => 'created overridden', 'EN' => 'created overridden'], $assignedMetadata->getLabel());
    }

    public function testEditingResourceKind() {
        $client = self::createAdminClient();
        $client->apiRequest(
            'PUT',
            self::oneEntityEndpoint($this->resourceKind->getId()),
            [
                'label' => ['PL' => 'modified', 'EN' => 'modified'],
                'metadataList' => [
                    ['id' => $this->metadata2->getId()],
                    ['id' => $this->metadata1->getId(), 'placeholder' => ['PL' => 'modified', 'EN' => 'modified']],
                    ['id' => SystemMetadata::PARENT],
                ],
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $client = self::createClient();
        $resourceKindRepository = $client->getContainer()->get(ResourceKindRepository::class);
        /** @var ResourceKind $resourceKind */
        $resourceKind = $resourceKindRepository->findOne($this->resourceKind->getId());
        $this->assertEquals(['PL' => 'modified', 'EN' => 'modified'], $resourceKind->getLabel());
        $this->assertEquals(
            [
                $this->metadata2->getId(),
                $this->metadata1->getId(),
                SystemMetadata::PARENT,
                SystemMetadata::REPRODUCTOR,
                SystemMetadata::RESOURCE_LABEL,
            ],
            $resourceKind->getMetadataIds()
        );
    }

    public function testEditingResourceKindMetadataClearsUnknownLanguages() {
        $client = self::createAdminClient();
        $client->apiRequest(
            'PUT',
            self::oneEntityEndpoint($this->resourceKind->getId()),
            [
                'label' => ['PL' => 'modified', 'EN' => 'modified'],
                'metadataList' => [
                    ['id' => $this->metadata2->getId()],
                    ['id' => $this->metadata1->getId(), 'placeholder' => ['PL' => 'modified', 'DE' => 'modified'],],
                    ['id' => SystemMetadata::PARENT],
                ],
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $updatedRk = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('DE', $updatedRk['metadataList'][1]['placeholder']);
        $this->assertEquals('modified', $updatedRk['metadataList'][1]['placeholder']['PL']);
    }

    public function testSettingWorkflowForExistingResourceKind() {
        $workflow = $this->createWorkflow(
            ['PL' => 'test', 'EN' => 'test'],
            'books',
            [
                new ResourceWorkflowPlace(['PL' => 'A', 'EN' => 'A'], 'A'),
                new ResourceWorkflowPlace(['PL' => 'B', 'EN' => 'B'], 'B'),
            ],
            [
                new ResourceWorkflowTransition(['PL' => 'przejscie', 'EN' => 'transition'], ['A'], ['B']),
            ]
        );
        $resource = $this->handleCommandBypassingFirewall(
            new ResourceCreateCommand(
                $this->resourceKind,
                ResourceContents::fromArray(
                    [
                        $this->metadata1->getId() => ['test1'],
                        $this->metadata2->getId() => ['test2'],
                    ]
                )
            )
        );
        $client = self::createAdminClient();
        $client->apiRequest(
            'PUT',
            self::oneEntityEndpoint($this->resourceKind->getId()),
            [
                'label' => ['PL' => 'test', 'EN' => 'test'],
                'metadataList' => [
                    ['id' => $this->metadata2->getId()],
                    ['id' => $this->metadata1->getId()],
                    ['id' => SystemMetadata::PARENT],
                ],
                'workflowId' => $workflow->getId(),
            ]
        );
        $this->assertStatusCode(200, $client->getResponse());
        $client = self::createClient();
        /** @var ResourceKindRepository $resourceKindRepository */
        $resourceRepository = $client->getContainer()->get(ResourceRepository::class);
        $resource = $resourceRepository->findOne($resource->getId());
        $this->assertNotNull($resource->getWorkflow());
        $this->assertEquals($workflow->getId(), $resource->getWorkflow()->getId());
        $this->assertCount(1, $resource->getWorkflow()->getPlaces($resource));
        $this->assertEquals('A', $resource->getWorkflow()->getPlaces($resource)[0]->getId());
    }

    public function testDeletingResourceKind() {
        $client = self::createAdminClient();
        $client->apiRequest('DELETE', self::oneEntityEndpoint($this->resourceKind->getId()));
        $this->assertStatusCode(204, $client->getResponse());
        $client = self::createClient();
        /** @var ResourceKindRepository $resourceKindRepository */
        $resourceKindRepository = $client->getContainer()->get(ResourceKindRepository::class);
        /** @var MetadataRepository $metadataRepository */
        $metadataRepository = $client->getContainer()->get(MetadataRepository::class);
        $this->assertFalse($resourceKindRepository->exists($this->resourceKind->getId()));
        $this->assertTrue($metadataRepository->exists($this->metadata1->getId()));
        $this->assertTrue($metadataRepository->exists($this->metadata2->getId()));
    }

    public function testDeletingUsedResourceKindFails() {
        $this->handleCommandBypassingFirewall(
            new ResourceCreateCommand(
                $this->resourceKind,
                ResourceContents::fromArray(
                    [
                        $this->metadata1->getId() => ['test1'],
                        $this->metadata2->getId() => ['test2'],
                    ]
                )
            )
        );
        $client = self::createAdminClient();
        $client->apiRequest('DELETE', self::oneEntityEndpoint($this->resourceKind->getId()));
        $this->assertStatusCode(400, $client->getResponse());
        $client = self::createClient();
        /** @var ResourceKindRepository $resourceKindRepository */
        $resourceKindRepository = $client->getContainer()->get(ResourceKindRepository::class);
        /** @var MetadataRepository $metadataRepository */
        $metadataRepository = $client->getContainer()->get(MetadataRepository::class);
        $this->assertTrue($resourceKindRepository->exists($this->resourceKind->getId()));
        $this->assertTrue($metadataRepository->exists($this->metadata1->getId()));
        $this->assertTrue($metadataRepository->exists($this->metadata2->getId()));
    }

    public function testFetchingResourceKindsFailsWhenInvalidResourceClass() {
        $client = self::createAdminClient();
        $client->apiRequest('GET', self::ENDPOINT, [], ['resourceClasses' => ['resourceClass']]);
        $this->assertStatusCode(400, $client->getResponse());
    }
}
