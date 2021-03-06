<?php
namespace Repeka\Application\Workflow;

use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowTransition;
use Repeka\Domain\Exception\ResourceWorkflow\CannotApplyTransitionException;
use Repeka\Domain\Utils\EntityUtils;
use Repeka\Domain\Workflow\ResourceWorkflowDriver;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class SymfonyResourceWorkflowDriver implements ResourceWorkflowDriver {
    /** @var Workflow */
    private $workflow;
    /** @var ResourceWorkflow */
    private $resourceWorkflow;

    public function __construct(ResourceWorkflow $resourceWorkflow) {
        $this->resourceWorkflow = $resourceWorkflow;
    }

    private function getWorkflow(): Workflow {
        if (!$this->workflow) {
            $builder = new DefinitionBuilder();
            $builder->addPlaces(EntityUtils::mapToIds($this->resourceWorkflow->getPlaces()));
            $builder->addTransitions(
                array_map(
                    function (ResourceWorkflowTransition $transition) {
                        return new Transition($transition->getId(), $transition->getFromIds(), $transition->getToIds());
                    },
                    $this->resourceWorkflow->getTransitions()
                )
            );
            $definition = $builder->build();
            $this->workflow = new Workflow($definition);
        }
        return $this->workflow;
    }

    /** @return string[] */
    public function getPlaces(ResourceEntity $resource): array {
        return array_keys($this->getWorkflow()->getMarking($resource)->getPlaces());
    }

    /** @return string[] */
    public function getTransitions(ResourceEntity $resource): array {
        return array_map(
            function (Transition $transition) {
                return $transition->getName();
            },
            $this->getWorkflow()->getEnabledTransitions($resource)
        );
    }

    public function apply(ResourceEntity $resource, string $transitionId): ResourceEntity {
        try {
            $this->getWorkflow()->apply($resource, $transitionId);
        } catch (LogicException $e) {
            throw new CannotApplyTransitionException($transitionId, $this->resourceWorkflow, $e);
        }
        return $resource;
    }

    public function setCurrentPlaces(ResourceEntity $resourceEntity, array $places): ResourceEntity {
        // we want to store places as array of strings; symfony stores them as associatiave array with placeId=>true
        $associativePlaces = [];
        foreach ($places as $place) {
            $place = is_string($place) ? $place : $place['id'];
            $associativePlaces[$place] = true;
        }
        $resourceEntity->setMarking($associativePlaces);
        return $resourceEntity;
    }
}
