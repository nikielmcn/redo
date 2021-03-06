<?php
namespace Repeka\Domain\Workflow;

use Repeka\Domain\Entity\ResourceContents;
use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Entity\User;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowPlace;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowTransition;
use Repeka\Domain\Utils\EntityUtils;

class TransitionPossibilityChecker {
    /** @var TransitionAssigneeChecker */
    private $transitionAssigneeChecker;

    public function __construct(TransitionAssigneeChecker $transitionAssigneeChecker) {
        $this->transitionAssigneeChecker = $transitionAssigneeChecker;
    }

    public function check(
        ResourceEntity $resource,
        ResourceContents $newContents,
        ResourceWorkflowTransition $transition,
        User $executor
    ): TransitionPossibilityCheckResult {
        return new TransitionPossibilityCheckResult(
            $this->determineMissingMetadataIds($resource, $newContents, $transition),
            !$this->transitionAssigneeChecker->canApplyTransition($resource, $transition, $executor)
        );
    }

    private function determineMissingMetadataIds(
        ResourceEntity $resource,
        ResourceContents $resourceContents,
        ResourceWorkflowTransition $transition
    ): array {
        if (!$resource->hasWorkflow()) {
            return [];
        }
        $workflow = $resource->getWorkflow();
        $targetPlaces = EntityUtils::getByIds($transition->getToIds(), $workflow->getPlaces());
        $missingMetadataIds = [];
        foreach ($targetPlaces as $targetPlace) {
            /** @var ResourceWorkflowPlace $targetPlace */
            $metadataIdsMissingForPlace = $targetPlace->getMissingRequiredMetadataIds($resourceContents, $resource->getKind());
            $missingMetadataIds = array_merge($missingMetadataIds, $metadataIdsMissingForPlace);
        }
        return array_values(array_unique($missingMetadataIds));
    }

    /**
     * Gets assignee and auto-assignee metadata list for each of transition tos, merges these lists, removes duplicates and returns only IDs
     * @return int[]
     */
    public function getAssigneeMetadataIds(ResourceEntity $resource, ResourceWorkflowTransition $transition): array {
        return $this->transitionAssigneeChecker->getAssigneeMetadataIds($resource, $transition);
    }

    public function getAutoAssignMetadataIds(ResourceEntity $resource, ResourceWorkflowTransition $transition): array {
        return $this->transitionAssigneeChecker->getAutoAssignMetadataIds($resource, $transition);
    }
}
