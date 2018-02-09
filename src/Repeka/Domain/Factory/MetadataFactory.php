<?php
namespace Repeka\Domain\Factory;

use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Entity\MetadataControl;
use Repeka\Domain\UseCase\Metadata\MetadataCreateCommand;

class MetadataFactory {
    public function create(MetadataCreateCommand $command) {
        return Metadata::create(
            $command->getResourceClass(),
            new MetadataControl($command->getControlName()),
            $command->getName(),
            $command->getLabel(),
            $command->getPlaceholder(),
            $command->getDescription(),
            $command->getConstraints(),
            $command->isShownInBrief()
        );
    }

    public function createWithParent(array $newChildMetadata, Metadata $parent) {
        $metadata = MetadataFactory::create(MetadataCreateCommand::fromArray($newChildMetadata));
        $metadata->setParent($parent);
        return $metadata;
    }

    public function createWithBaseAndParent(Metadata $base, Metadata $parent, array $newChildMetadata) {
        $metadata = Metadata::createChild($base, $parent);
        $metadata->update(
            $newChildMetadata['label'],
            $newChildMetadata['placeholder'],
            $newChildMetadata['description'],
            $newChildMetadata['constraints'],
            $newChildMetadata['shownInBrief']
        );
        return $metadata;
    }

    public function removeUnmodifiedConstraints(array $constraints, array $baseConstraints): array {
        foreach (array_keys($baseConstraints) as $constraintName) {
            if (!array_key_exists($constraintName, $constraints)) {
                continue;
            }
            $baseConstraint = $baseConstraints[$constraintName];
            $concreteConstraint = $constraints[$constraintName];
            if (is_array($baseConstraint)) {
                array_multisort($baseConstraint);
            }
            if (is_array($concreteConstraint)) {
                array_multisort($concreteConstraint);
            }
            if ($concreteConstraint == $baseConstraint) {
                unset($constraints[$constraintName]);
            }
        }
        return $constraints;
    }
}
