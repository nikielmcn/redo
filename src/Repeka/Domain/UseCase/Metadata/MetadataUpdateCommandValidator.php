<?php
namespace Repeka\Domain\UseCase\Metadata;

use Repeka\Domain\Cqrs\Command;
use Repeka\Domain\Validation\CommandAttributesValidator;
use Repeka\Domain\Validation\Rules\ConstraintArgumentsAreValidRule;
use Repeka\Domain\Validation\Rules\ConstraintSetMatchesControlRule;
use Repeka\Domain\Validation\Rules\ResourceKindConstraintIsUserIfMetadataDeterminesAssigneeRule;
use Respect\Validation\Validatable;
use Respect\Validation\Validator;

class MetadataUpdateCommandValidator extends CommandAttributesValidator {
    /** @var ConstraintSetMatchesControlRule */
    private $constraintSetMatchesControlRule;
    /** @var ConstraintArgumentsAreValidRule */
    private $constraintArgumentsAreValidRule;
    /** @var ResourceKindConstraintIsUserIfMetadataDeterminesAssigneeRule */
    private $rkConstraintIsUserIfNecessaryRule;

    public function __construct(
        ConstraintSetMatchesControlRule $constraintSetMatchesControlRule,
        ConstraintArgumentsAreValidRule $constraintArgumentsAreValidRule,
        ResourceKindConstraintIsUserIfMetadataDeterminesAssigneeRule $rkConstraintIsUserIfNecessaryRule
    ) {
        $this->constraintSetMatchesControlRule = $constraintSetMatchesControlRule;
        $this->constraintArgumentsAreValidRule = $constraintArgumentsAreValidRule;
        $this->rkConstraintIsUserIfNecessaryRule = $rkConstraintIsUserIfNecessaryRule;
    }

    /** @param MetadataUpdateCommand $command */
    public function getValidator(Command $command): Validatable {
        return Validator
            ::attribute('metadataId', Validator::intVal())
            ->attribute('newLabel', Validator::arrayType())
            ->attribute('newPlaceholder', Validator::arrayType())
            ->attribute('newDescription', Validator::arrayType())
            ->attribute('newShownInBrief', Validator::boolType())
            ->attribute('newCopyToChildResource', Validator::boolType())
            ->attribute('newConstraints', $this->constraintSetMatchesControlRule->forMetadataId($command->getMetadataId()))
            ->attribute('newConstraints', $this->constraintArgumentsAreValidRule)
            ->attribute('newConstraints', $this->rkConstraintIsUserIfNecessaryRule->forMetadataId($command->getMetadataId()));
    }
}
