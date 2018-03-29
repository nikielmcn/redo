<?php
namespace Repeka\Domain\UseCase\ResourceWorkflow;

use Repeka\Domain\Cqrs\AbstractCommand;
use Repeka\Domain\Cqrs\AdjustableCommand;
use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Entity\Workflow\ResourceWorkflowPlace;

class ResourceWorkflowUpdateCommand extends AbstractCommand implements AdjustableCommand {
    private $name;
    private $places;
    private $transitions;
    private $diagram;
    private $thumbnail;
    /** @var ResourceWorkflow */
    private $workflow;

    public function __construct(
        ResourceWorkflow $workflow,
        array $name,
        array $places,
        array $transitions,
        $diagram,
        $thumbnail
    ) {
        $this->workflow = $workflow;
        $this->name = $name;
        $this->places = $places;
        $this->transitions = $transitions;
        $this->diagram = $diagram;
        $this->thumbnail = $thumbnail;
    }

    public function getName(): array {
        return $this->name;
    }

    public function getPlaces(): array {
        return $this->places;
    }

    public function getTransitions(): array {
        return $this->transitions;
    }

    public function getDiagram() {
        return $this->diagram;
    }

    public function getThumbnail() {
        return $this->thumbnail;
    }

    public function getWorkflow(): ResourceWorkflow {
        return $this->workflow;
    }
}
