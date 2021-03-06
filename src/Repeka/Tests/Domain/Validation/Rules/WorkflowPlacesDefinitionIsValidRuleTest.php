<?php
namespace Repeka\Tests\Domain\Validation\Rules;

use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowPlace;
use Repeka\Domain\UseCase\ResourceWorkflow\ResourceWorkflowCreateCommand;
use Repeka\Domain\UseCase\ResourceWorkflow\ResourceWorkflowCreateCommandValidator;
use Repeka\Domain\UseCase\ResourceWorkflow\ResourceWorkflowUpdateCommand;
use Repeka\Domain\Validation\Rules\NoAssigneeMetadataInFirstPlaceRule;
use Repeka\Domain\Validation\Rules\NotBlankInAllLanguagesRule;
use Repeka\Domain\Validation\Rules\ResourceClassExistsRule;
use Repeka\Domain\Validation\Rules\WorkflowPlacesDefinitionIsValidRule;
use Repeka\Domain\Validation\Rules\WorkflowTransitionNamesMatchInAllLanguagesRule;
use Repeka\Domain\Validation\Rules\WorkflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule;
use Repeka\Domain\Validation\Rules\WorkflowTransitionsDefinitionIsValidRule;
use Repeka\Tests\Traits\StubsTrait;

/** @SuppressWarnings("PHPMD.LongVariable") */
class WorkflowPlacesDefinitionIsValidRuleTest extends \PHPUnit_Framework_TestCase {
    use StubsTrait;

    /** @var ResourceWorkflowCreateCommandValidator */
    private $validator;

    protected function setUp() {
        $entityExistsRule = $this->createEntityExistsMock(true);
        $notBlankInAllLanguagesRule = $this->createRuleMock(NotBlankInAllLanguagesRule::class, true);
        $resourceClassExistsRule = $this->createRuleMock(ResourceClassExistsRule::class, true);
        $workflowPlacesDefinitionIsValidRule = new WorkflowPlacesDefinitionIsValidRule($entityExistsRule);
        $workflowTransitionsDefinitionIsValidRule = $this->createRuleMock(WorkflowTransitionsDefinitionIsValidRule::class, true);
        $workflowTransitionNamesMatchInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(WorkflowTransitionNamesMatchInAllLanguagesRule::class, "withPlaces", true);
        $noAssigneeMetadataInFirstPlaceRule = $this->createRuleMock(NoAssigneeMetadataInFirstPlaceRule::class, true);
        $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(
                WorkflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule::class,
                "withPlaces",
                true
            );
        $this->validator = new ResourceWorkflowCreateCommandValidator(
            $notBlankInAllLanguagesRule,
            $resourceClassExistsRule,
            $workflowTransitionsDefinitionIsValidRule,
            $workflowPlacesDefinitionIsValidRule,
            $workflowTransitionNamesMatchInAllLanguagesRule,
            $noAssigneeMetadataInFirstPlaceRule,
            $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule
        );
    }

    public function testValidWithPlaceAsArray() {
        $command = new ResourceWorkflowCreateCommand([], [['label' => ['PL' => 'A']]], [], 'books', null, null);
        $this->validator->validate($command);
    }

    public function testValidWithPlaceAsArrayWithIdentifier() {
        $command = new ResourceWorkflowCreateCommand([], [['id' => 'fooId', 'label' => []]], [], 'books', null, null);
        $this->validator->validate($command);
    }

    public function testValidWithPlaceAsArrayWithRequiredMetadataIds() {
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'requiredMetadataIds' => [1, 2, 3],
                    'lockedMetadataIds' => [4, 5],
                    'assigneeMetadataIds' => [6],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->validator->validate($command);
    }

    public function testValidWithPlaceAsObjects() {
        $command = new ResourceWorkflowCreateCommand([], [new ResourceWorkflowPlace([])], [], 'books', null, null);
        $this->validator->validate($command);
    }

    public function testInvalidWithNoPlaces() {
        $command = new ResourceWorkflowCreateCommand([], [], [], 'books', null, null);
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWithNoPlacesAndExistingWorkflow() {
        $workflow = $this->createMock(ResourceWorkflow::class);
        $command = new ResourceWorkflowUpdateCommand($workflow, [], [], [], null, null);
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWithInvalidPlace() {
        $command = new ResourceWorkflowCreateCommand([], [[]], [], 'books', null, null);
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWithExtraDataInPlace() {
        $command = new ResourceWorkflowCreateCommand([], [['label' => [], 'extra' => '']], [], 'books', null, null);
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWithLabelNotArray() {
        $command = new ResourceWorkflowCreateCommand([], [['label' => '']], [], 'books', null, null);
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWhenLockedMetadataIdsNotAnArray() {
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'lockedMetadataIds' => 1,
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWhenRequiredMetadataIdsDoNotExist() {
        $entityExistsMock = $this->createEntityExistsMock(false);
        $notBlankInAllLanguagesRule = $this->createRuleMock(NotBlankInAllLanguagesRule::class, true);
        $resourceClassExistsRule = $this->createRuleMock(ResourceClassExistsRule::class, true);
        $workflowPlacesDefinitionIsValidRule = new WorkflowPlacesDefinitionIsValidRule($entityExistsMock);
        $workflowTransitionsDefinitionIsValidRule = $this->createRuleMock(WorkflowTransitionsDefinitionIsValidRule::class, true);
        $workflowTransitionNamesMatchInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(WorkflowTransitionNamesMatchInAllLanguagesRule::class, "withPlaces", true);
        $noAssigneeMetadataInFirstPlaceRule = $this->createRuleMock(NoAssigneeMetadataInFirstPlaceRule::class, true);
        $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(
                WorkflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule::class,
                "withPlaces",
                true
            );
        $validator = new ResourceWorkflowCreateCommandValidator(
            $notBlankInAllLanguagesRule,
            $resourceClassExistsRule,
            $workflowTransitionsDefinitionIsValidRule,
            $workflowPlacesDefinitionIsValidRule,
            $workflowTransitionNamesMatchInAllLanguagesRule,
            $noAssigneeMetadataInFirstPlaceRule,
            $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule
        );
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'requiredMetadataIds' => [1],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($validator->isValid($command));
    }

    public function testInvalidWhenLockedMetadataIdsDoNotExist() {
        $entityExistsMock = $this->createEntityExistsMock(false);
        $notBlankInAllLanguagesRule = $this->createRuleMock(NotBlankInAllLanguagesRule::class, true);
        $resourceClassExistsRule = $this->createRuleMock(ResourceClassExistsRule::class, true);
        $workflowPlacesDefinitionIsValidRule = new WorkflowPlacesDefinitionIsValidRule($entityExistsMock);
        $workflowTransitionsDefinitionIsValidRule =
            $this->createRuleMock(WorkflowTransitionsDefinitionIsValidRule::class, true);
        $workflowTransitionNamesMatchInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(WorkflowTransitionNamesMatchInAllLanguagesRule::class, "withPlaces", true);
        $noAssigneeMetadataInFirstPlaceRule = $this->createRuleMock(NoAssigneeMetadataInFirstPlaceRule::class, true);
        $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(
                WorkflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule::class,
                "withPlaces",
                true
            );
        $validator = new ResourceWorkflowCreateCommandValidator(
            $notBlankInAllLanguagesRule,
            $resourceClassExistsRule,
            $workflowTransitionsDefinitionIsValidRule,
            $workflowPlacesDefinitionIsValidRule,
            $workflowTransitionNamesMatchInAllLanguagesRule,
            $noAssigneeMetadataInFirstPlaceRule,
            $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule
        );
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'lockedMetadataIds' => [1],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($validator->isValid($command));
    }

    public function testInvalidWhenAssigneeMetadataIdsDoNotExist() {
        $entityExistsMock = $this->createEntityExistsMock(false);
        $notBlankInAllLanguagesRule = $this->createRuleMock(NotBlankInAllLanguagesRule::class, true);
        $resourceClassExistsRule = $this->createRuleMock(ResourceClassExistsRule::class, true);
        $workflowPlacesDefinitionIsValidRule = new WorkflowPlacesDefinitionIsValidRule($entityExistsMock);
        $workflowTransitionsDefinitionIsValidRule = $this->createRuleMock(WorkflowTransitionsDefinitionIsValidRule::class, true);
        $workflowTransitionNamesMatchInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(WorkflowTransitionNamesMatchInAllLanguagesRule::class, "withPlaces", true);
        $noAssigneeMetadataInFirstPlaceRule = $this->createRuleMock(NoAssigneeMetadataInFirstPlaceRule::class, true);
        $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule =
            $this->createRuleWithFactoryMethodMock(
                WorkflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule::class,
                "withPlaces",
                true
            );
        $validator = new ResourceWorkflowCreateCommandValidator(
            $notBlankInAllLanguagesRule,
            $resourceClassExistsRule,
            $workflowTransitionsDefinitionIsValidRule,
            $workflowPlacesDefinitionIsValidRule,
            $workflowTransitionNamesMatchInAllLanguagesRule,
            $noAssigneeMetadataInFirstPlaceRule,
            $workflowTransitionsAndPlacesNamesSpecifiedInAllLanguagesRule
        );
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'assigneeMetadataIds' => [1],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($validator->isValid($command));
    }

    public function testInvalidWhenRequiredMetadataIdsNotAnArray() {
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'requiredMetadataIds' => 1,
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testInvalidWhenObsoletePluginsConfigIsGiven() {
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'pluginsConfig' => ['repekaOcr' => ['metadataToOcr' => 'Tytuł']],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertFalse($this->validator->isValid($command));
    }

    public function testValidWhenNewPluginsConfigIsGiven() {
        $command = new ResourceWorkflowCreateCommand(
            [],
            [
                [
                    'id' => 'fooId',
                    'label' => ['PL' => 'A'],
                    'pluginsConfig' => [['name' => 'repekaOcr', 'config' => ['metadataToOcr' => 'Tytuł']]],
                ],
            ],
            [],
            'books',
            null,
            null
        );
        $this->assertTrue($this->validator->isValid($command));
    }
}
