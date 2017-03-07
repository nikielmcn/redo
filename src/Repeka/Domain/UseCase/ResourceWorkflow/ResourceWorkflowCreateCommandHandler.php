<?php
namespace Repeka\Domain\UseCase\ResourceWorkflow;

use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Repository\ResourceWorkflowRepository;

class ResourceWorkflowCreateCommandHandler {
    /** @var ResourceWorkflowRepository */
    private $workflowRepository;

    public function __construct(ResourceWorkflowRepository $workflowRepository) {
        $this->workflowRepository = $workflowRepository;
    }

    public function handle(ResourceWorkflowCreateCommand $command): ResourceWorkflow {
        $workflow = new ResourceWorkflow($command->getName());
        return $this->workflowRepository->save($workflow);
    }
}