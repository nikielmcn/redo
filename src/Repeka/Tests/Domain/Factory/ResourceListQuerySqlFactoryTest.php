<?php
namespace Repeka\Tests\Domain\Factory;

use Repeka\Domain\Factory\ResourceListQuerySqlFactory;
use Repeka\Domain\UseCase\Resource\ResourceListQuery;

class ResourceListQuerySqlFactoryTest extends \PHPUnit_Framework_TestCase {
    public function testEmptyPageQuery() {
        $query = ResourceListQuery::builder()->build();
        $sql = (new ResourceListQuerySqlFactory($query))->getPageQuery();
        $this->assertContains('SELECT r.* FROM', $sql);
        $this->assertContains('ORDER BY', $sql);
        $this->assertNotContains('LIMIT', $sql);
    }

    public function testEmptyCountQuery() {
        $query = ResourceListQuery::builder()->build();
        $sql = (new ResourceListQuerySqlFactory($query))->getTotalCountQuery();
        $this->assertContains('SELECT COUNT(id) FROM', $sql);
        $this->assertNotContains('ORDER BY', $sql);
        $this->assertNotContains('LIMIT', $sql);
    }

    public function testPaginate() {
        $query = ResourceListQuery::builder()->setPage(3)->setResultsPerPage(10)->build();
        $sql = (new ResourceListQuerySqlFactory($query))->getPageQuery();
        $this->assertContains('LIMIT 10', $sql);
        $this->assertContains('20', $sql);
    }

    public function testFilterByIds() {
        $query = ResourceListQuery::builder()->filterByIds([38, 44])->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertContains('id IN', $factory->getPageQuery());
        $this->assertArrayHasKey('ids', $factory->getParams());
        $this->assertEquals([38, 44], $factory->getParams()['ids']);
    }

    public function testFilterByResourceClasses() {
        $query = ResourceListQuery::builder()->filterByResourceClass('books')->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertContains('resource_class IN', $factory->getPageQuery());
        $this->assertArrayHasKey('resourceClasses', $factory->getParams());
        $this->assertEquals(['books'], $factory->getParams()['resourceClasses']);
    }

    public function testFilterByContents() {
        $query = ResourceListQuery::builder()->filterByContents([1 => 'PHP'])->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertContains('mFilter0', $factory->getPageQuery());
        $this->assertContains("r.contents->'1'", $factory->getPageQuery());
        $this->assertContains("~*", $factory->getPageQuery());
        $this->assertArrayHasKey('mFilter0', $factory->getParams());
        $this->assertEquals('PHP', $factory->getParams()['mFilter0']);
    }

    public function testFilterByNumber() {
        $query = ResourceListQuery::builder()->filterByContents([1 => 40])->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertContains('mFilter0', $factory->getPageQuery());
        $this->assertContains("r.contents->'1'", $factory->getPageQuery());
        $this->assertNotContains("~*", $factory->getPageQuery());
        $this->assertArrayHasKey('mFilter0', $factory->getParams());
        $this->assertEquals(40, $factory->getParams()['mFilter0']);
    }

    public function testFilterByAlternativeContents() {
        $query = ResourceListQuery::builder()
            ->filterByContents([5 => 'PHP'])
            ->filterByContents([6 => 'alternative'])
            ->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertContains('m0', $factory->getPageQuery());
        $this->assertContains('m0', $factory->getPageQuery());
        $this->assertContains('mFilter0', $factory->getPageQuery());
        $this->assertContains('mFilter1', $factory->getPageQuery());
        $this->assertArrayHasKey('mFilter0', $factory->getParams());
        $this->assertArrayHasKey('mFilter1', $factory->getParams());
        $this->assertEquals('PHP', $factory->getParams()['mFilter0']);
        $this->assertEquals('alternative', $factory->getParams()['mFilter1']);
        $this->assertContains("r.contents->'5'", $factory->getPageQuery());
        $this->assertContains("r.contents->'6'", $factory->getPageQuery());
        $this->assertContains(' OR ', $factory->getPageQuery());
    }

    public function testFilterByNoContentsHasNoAlternatives() {
        $query = ResourceListQuery::builder()
            ->build();
        $factory = new ResourceListQuerySqlFactory($query);
        $this->assertNotContains(' OR ', $factory->getPageQuery());
    }
}
