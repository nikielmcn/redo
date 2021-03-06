<?php
namespace Repeka\Tests\Domain\UseCase\Metadata;

use Repeka\Domain\Exception\InvalidCommandException;
use Repeka\Domain\Repository\LanguageRepository;
use Repeka\Domain\Repository\MetadataRepository;
use Repeka\Domain\UseCase\Metadata\MetadataCreateCommand;
use Repeka\Domain\UseCase\Metadata\MetadataCreateCommandValidator;
use Repeka\Domain\Validation\Rules\ConstraintArgumentsAreValidRule;
use Repeka\Domain\Validation\Rules\ConstraintSetMatchesControlRule;
use Repeka\Domain\Validation\Rules\IsValidControlRule;
use Repeka\Domain\Validation\Rules\MetadataGroupExistsRule;
use Repeka\Domain\Validation\Rules\NotBlankInAllLanguagesRule;
use Repeka\Domain\Validation\Rules\ResourceClassExistsRule;
use Repeka\Tests\Traits\StubsTrait;
use Respect\Validation\Exceptions\ValidationException;

class MetadataCreateCommandValidatorTest extends \PHPUnit_Framework_TestCase {
    use StubsTrait;

    /** @var LanguageRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $languageRepositoryStub;
    /** @var MetadataCreateCommandValidator */
    private $validator;
    /** @var ConstraintArgumentsAreValidRule|\PHPUnit_Framework_MockObject_MockObject */
    private $constraintArgumentsAreValidRule;
    /** @var ResourceClassExistsRule|\PHPUnit_Framework_MockObject_MockObject */
    private $containsResourceClassRule;
    /** @var MetadataRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $metadataRepository;

    protected function setUp() {
        $this->languageRepositoryStub = $this->createMock(LanguageRepository::class);
        $this->languageRepositoryStub->expects($this->atLeastOnce())->method('getAvailableLanguageCodes')->willReturn(['PL']);
        $this->constraintArgumentsAreValidRule = $this->createMock(ConstraintArgumentsAreValidRule::class);
        $this->containsResourceClassRule = $this->createMock(ResourceClassExistsRule::class);
        $this->metadataRepository = $this->createMock(MetadataRepository::class);
        $this->validator = new MetadataCreateCommandValidator(
            new NotBlankInAllLanguagesRule($this->languageRepositoryStub),
            new IsValidControlRule(['text', 'textarea']),
            $this->createMock(ConstraintSetMatchesControlRule::class),
            $this->constraintArgumentsAreValidRule,
            $this->containsResourceClassRule,
            new MetadataGroupExistsRule([['id'=>'group0'], ['id'=>'group1']]),
            $this->metadataRepository
        );
    }

    public function testPassingValidation() {
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'text', 'books', [], 'group1');
        $this->validator->validate($command);
    }

    public function testFailsValidationBecauseOfInvalidControl() {
        $this->expectException(InvalidCommandException::class);
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'blabla', 'books');
        $this->validator->validate($command);
    }

    public function testFailsValidationBecauseOfInvalidResourceClass() {
        $this->expectException(InvalidCommandException::class);
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'blabla', 'invalidResourceClass');
        $this->validator->validate($command);
    }

    public function testTellsThatControlIsInvalid() {
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'blabla', 'books');
        try {
            $this->validator->validate($command);
        } catch (InvalidCommandException $e) {
            $this->assertEquals('controlName', $e->getViolations()[0]['field']);
        }
    }

    public function testFailsValidationBecauseOfNoLabelInMainLanguage() {
        $this->expectException(InvalidCommandException::class);
        $command = new MetadataCreateCommand('nazwa', ['EN' => 'Test'], [], [], 'text', 'books');
        $this->validator->validate($command);
    }

    public function testFailsValidationBecauseThereIsNoName() {
        $this->expectException(InvalidCommandException::class);
        $command = new MetadataCreateCommand('', ['PL' => 'Test'], [], [], 'text', 'books');
        $this->validator->validate($command);
    }

    public function testFailsValidationWhenReferencedResourceKindDoesNotExist() {
        $this->expectException(InvalidCommandException::class);
        $this->constraintArgumentsAreValidRule->method('validate')->willReturn(false);
        $this->constraintArgumentsAreValidRule->method('assert')->willThrowException(new ValidationException());
        $constraints = ['resourceKind' => [1]];
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'relationship', 'books', $constraints);
        $this->validator->validate($command);
    }

    public function testValidationSucceedsWhenReferencedResourceKindExists() {
        $this->constraintArgumentsAreValidRule->method('validate')->willReturn(true);
        $constraints = ['resourceKind' => [1], 'maxCount' => 0];
        $command = new MetadataCreateCommand('nazwa', ['PL' => 'Test'], [], [], 'relationship', 'books', $constraints);
        $this->validator->validate($command);
    }

    public function testTellsThatLabelIsInvalid() {
        $command = new MetadataCreateCommand('nazwa', [], [], [], 'text', 'books');
        try {
            $this->validator->validate($command);
        } catch (InvalidCommandException $e) {
            $this->assertEquals('label', $e->getViolations()[0]['field']);
        }
    }

    public function testFailsIfMetadataWithGivenNameExists() {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('metadataNameIsNotUnique');
        $this->metadataRepository->expects($this->once())->method('findByQuery')->willReturn([$this->createMetadataMock()]);
        $command = new MetadataCreateCommand('notUniqueName', ['PL' => 'Test'], [], [], 'text', 'books');
        $this->validator->validate($command);
    }

    public function testFailsWhenGroupDoesNotExist() {
        $this->expectException(InvalidCommandException::class);
        $command = new MetadataCreateCommand('name', ['PL' => 'Test'], [], [], 'text', 'books', [], 'invalidGroup');
        $this->validator->validate($command);
    }
}
