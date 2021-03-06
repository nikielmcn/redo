<?php
namespace Repeka\Tests\Integration\FullTextSearch;

use Repeka\Application\Elasticsearch\ESIndexManager;
use Repeka\Tests\IntegrationTestCaseWithoutDroppingDatabase;

class FtsIndexResourcesFromDatabaseCommandIntegrationTest extends IntegrationTestCaseWithoutDroppingDatabase {
    /** @var EsIndexManager */
    private $esIndexManager;

    protected function initializeDatabaseBeforeTheFirstTest() {
        $this->loadAllFixtures();
    }

    /** @before */
    public function init() {
        $this->esIndexManager = $this->container->get(ESIndexManager::class);
    }

    public function testMappingDatabaseToElasticsearchIndexWhenDoesNotExist() {
        $this->executeCommand('repeka:elasticsearch:delete-index');
        $this->assertFalse($this->esIndexManager->exists());
        $output = $this->executeCommand('repeka:fts:index-database');
        $this->assertContains('Index ' . $this->esIndexManager->getIndex() . 'does not exist', $output);
        $this->assertFalse($this->esIndexManager->exists());
    }

    public function testMappingDatabaseToElasticsearchIndexWhenExists() {
        $this->executeCommand('repeka:elasticsearch:create-index' . ' --delete-if-exists');
        $this->assertTrue($this->esIndexManager->exists());
        $output = $this->executeCommand('repeka:fts:index-database');
        $this->assertContains('All resources from the database have been inserted', $output);
    }
}
