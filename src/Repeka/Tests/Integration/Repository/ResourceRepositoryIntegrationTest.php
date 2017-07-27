<?php
namespace Repeka\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Repeka\Application\Entity\UserEntity;
use Repeka\Domain\Constants\SystemResourceKind;
use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\Repository\ResourceRepository;
use Repeka\Domain\UseCase\Metadata\MetadataListByResourceClassQuery;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindListQuery;
use Repeka\Domain\UseCase\User\UserListQuery;
use Repeka\Tests\IntegrationTestCase;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class ResourceRepositoryIntegrationTest extends IntegrationTestCase {
    /** @var EntityRepository|ResourceRepository */
    private $resourceRepository;
    /** @var EntityManagerInterface */
    private $em;

    public function setUp() {
        parent::setUp();
        $this->resourceRepository = $this->container->get(ResourceRepository::class);
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->loadAllFixtures();
    }

    public function testFindAll() {
        $resources = $this->resourceRepository->findAll();
        $this->assertCount(10, $resources); // 1 per every user + fixtures
    }

    public function testFindAllNonSystemResources() {
        $resources = $this->resourceRepository->findAllNonSystemResources('books');
        $this->assertCount(6, $resources); // fixtures only
        foreach ($resources as $resource) {
            $this->assertNotEquals(SystemResourceKind::USER, $resource->getKind()->getId());
        }
    }

    public function testFindAllNonSystemResourcesByAnotherResourceClass() {
        $resources = $this->resourceRepository->findAllNonSystemResources('dictionaries');
        $this->assertCount(0, $resources); // fixtures only
    }

    public function testFindTopLevel() {
        $topLevelResources = $this->resourceRepository->findTopLevel();
        $this->assertCount(4, $topLevelResources);
    }

    public function testFindChildren() {
        $ebooksCategoryId = $this->getEbooksCategoryResourceId();
        $this->assertNotNull($ebooksCategoryId);
        $ebooksChildren = $this->resourceRepository->findChildren($ebooksCategoryId);
        $this->assertCount(2, $ebooksChildren);
    }

    public function testDelete() {
        $ebooksCategoryId = $this->getEbooksCategoryResourceId();
        $ebooksCategory = $this->resourceRepository->findOne($ebooksCategoryId);
        $countBefore = count($this->resourceRepository->findAll());
        $this->resourceRepository->delete($ebooksCategory);
        $this->em->flush();
        $this->assertCount($countBefore - 1, $this->resourceRepository->findAll());
        $this->assertFalse($this->resourceRepository->exists($ebooksCategoryId));
    }

    public function testResourceCountByKind() {
        $bookResourceKind = $this->getBookResourceKind();
        $booksCount = $this->resourceRepository->countByResourceKind($bookResourceKind);
        $this->assertEquals(4, $booksCount);
    }

    public function testFindsResourcesAssignedToUser() {
        $user = $this->getBudynekUser();
        $resultsBeforeAssigning = $this->resourceRepository->findAssignedTo($user);
        $this->assertCount(0, $resultsBeforeAssigning);
        $book = $this->getPhpBookResource('books');
        $scannerMetadata = $this->getScannerBaseMetadata();
        $bookContents = $book->getContents();
        $bookContents[$scannerMetadata->getId()] = [$user->getUserData()->getId()];
        $book->updateContents($bookContents);
        $this->resourceRepository->save($book);
        $this->em->flush();
        $resultsAfterAssigning = $this->resourceRepository->findAssignedTo($user);
        $this->assertCount(1, $resultsAfterAssigning);
        $this->assertEquals($book->getId(), reset($resultsAfterAssigning)->getId());
    }

    private function getEbooksCategoryResourceId(): int {
        $connection = $this->container->get('doctrine.orm.entity_manager')->getConnection();
        $categoryNameMetadataId = $connection->query("SELECT id FROM metadata WHERE label->'EN' = '\"Category name\"';")->fetch()['id'];
        $ebooksCategoryId = $connection
            ->query("SELECT id FROM resource WHERE contents->'{$categoryNameMetadataId}' = '[\"E-booki\"]'")->fetch()['id'];
        $this->assertNotNull($ebooksCategoryId);
        return $ebooksCategoryId;
    }

    private function getBookResourceKind(): ResourceKind {
        $resourceKinds = $this->handleCommand(new ResourceKindListQuery('books', false));
        foreach ($resourceKinds as $resourceKind) {
            /** @var ResourceKind $resourceKind */
            if ($resourceKind->getLabel()['EN'] == 'Book') {
                return $resourceKind;
            }
        }
        throw new \RuntimeException("Resource kind 'Book' not found! This is a problem with the test, not the app.");
    }

    private function getBudynekUser(): UserEntity {
        /** @var UserEntity[] $users */
        $users = $this->handleCommand(new UserListQuery());
        foreach ($users as $user) {
            if ($user->getUsername() == 'budynek') {
                return $user;
            }
        }
        throw new \ErrorException("User not found");
    }

    private function getPhpBookResource(string $resourceClass): ResourceEntity {
        foreach ($this->resourceRepository->findAllNonSystemResources($resourceClass) as $resource) {
            $allValuesOfContents = call_user_func_array('array_merge', $resource->getContents());
            if (in_array('PHP - to można leczyć!', $allValuesOfContents)) {
                return $resource;
            }
        }
        throw new \ErrorException("Resource not found");
    }

    private function getScannerBaseMetadata(): Metadata {
        /** @var Metadata[] $metadataList */
        $metadataList = $this->handleCommand(new MetadataListByResourceClassQuery('books'));
        foreach ($metadataList as $metadata) {
            if ($metadata->getName() == 'Skanista') {
                return $metadata;
            }
        }
        throw new \ErrorException("Metadata not found");
    }
}
