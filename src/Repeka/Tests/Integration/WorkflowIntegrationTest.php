<?php
namespace Repeka\Tests\Integration;

use Repeka\DeveloperBundle\DataFixtures\ORM\AdminAccountFixture;
use Repeka\Tests\IntegrationTestCaseWithoutDroppingDatabase;

class WorkflowIntegrationTest extends IntegrationTestCaseWithoutDroppingDatabase {
    const ENDPOINT = '/api/workflows';

    protected function initializeDatabaseBeforeTheFirstTest() {
        self::loadFixture(new AdminAccountFixture());
    }

    public function testFetchingWorkflows() {
        $client = self::createAdminClient();
        $client->apiRequest('GET', self::ENDPOINT, [], ['resourceClass' => 'books']);
        $this->assertStatusCode(200, $client->getResponse());
    }

    public function testFetchingWorkflowsFailsWhenInvalidResourceClass() {
        $client = self::createAdminClient();
        $client->apiRequest('GET', self::ENDPOINT, [], ['resourceClass' => 'resourceClass']);
        $this->assertStatusCode('4XX', $client->getResponse());
    }
}
