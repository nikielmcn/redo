<?php
namespace Repeka\Tests\Domain\Validation\Rules;

use Repeka\Domain\Entity\MetadataControl;
use Repeka\Domain\Validation\MetadataConstraintManager;
use Repeka\Domain\Validation\Rules\ConstraintSetMatchesControlRule;
use Repeka\Tests\Traits\StubsTrait;

class ConstraintSetMatchesControlRuleTest extends \PHPUnit_Framework_TestCase {
    use StubsTrait;

    private $metadata;

    /** @var MetadataConstraintManager|\PHPUnit_Framework_MockObject_MockObject */
    private $constraintManager;

    /** @var ConstraintSetMatchesControlRule */
    private $rule;

    public function setUp() {
        $this->constraintManager = $this->createMock(MetadataConstraintManager::class);
        $this->rule = new ConstraintSetMatchesControlRule($this->constraintManager);
        $this->metadata = $this->createMetadataMock(1, null, MetadataControl::TEXT());
    }

    private function configureRequiredConstraints(array $requiredConstraints = ['foo', 'bar']) {
        $this->constraintManager->method('getSupportedConstraintNamesForControl')->willReturn($requiredConstraints);
    }

    public function testRejectsInvalidControls() {
        $this->expectException(\Exception::class);
        $this->rule->forControl('foo');
    }

    public function testFetchesRequiredConstraintsProperly() {
        $this->constraintManager->expects($this->once())->method('getSupportedConstraintNamesForControl')->with('text');
        $this->rule->forControl('text')->validate([]);
    }

    public function testAcceptsValidConstraints() {
        $this->configureRequiredConstraints();
        $this->assertTrue($this->rule->forControl('text')->validate(['foo' => null, 'bar' => null]));
        $this->assertTrue($this->rule->forMetadata($this->metadata)->validate(['foo' => null, 'bar' => null]));
    }

    public function testAcceptsValidEmptyConstraints() {
        $this->configureRequiredConstraints([]);
        $this->assertTrue($this->rule->forControl('text')->validate([]));
        $this->assertTrue($this->rule->forMetadata($this->metadata)->validate([]));
    }

    public function testAcceptsMissingConstraint() {
        $this->configureRequiredConstraints();
        $this->assertTrue($this->rule->forControl('text')->validate(['foo' => null]));
        $this->assertTrue($this->rule->forControl('text')->validate(['bar' => null]));
        $this->assertTrue($this->rule->forControl('text')->validate([]));
        $this->assertTrue($this->rule->forMetadata($this->metadata)->validate(['foo' => null]));
        $this->assertTrue($this->rule->forMetadata($this->metadata)->validate(['bar' => null]));
        $this->assertTrue($this->rule->forMetadata($this->metadata)->validate([]));
    }

    public function testRejectsExtraConstraints() {
        $this->configureRequiredConstraints();
        $this->assertFalse($this->rule->forControl('text')->validate(['foo' => null, 'bar' => null, 'baz' => null]));
        $this->assertFalse($this->rule->forMetadata($this->metadata)->validate(['foo' => null, 'bar' => null, 'baz' => null]));
    }

    public function testRejectsAnyConstraintsWhenNoneRequired() {
        $this->configureRequiredConstraints([]);
        $this->assertFalse($this->rule->forControl('text')->validate(['foo' => null]));
        $this->assertFalse($this->rule->forMetadata($this->metadata)->validate(['foo' => null]));
    }
}
