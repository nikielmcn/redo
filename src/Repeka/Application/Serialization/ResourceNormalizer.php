<?php
namespace Repeka\Application\Serialization;

use Repeka\Domain\Entity\ResourceEntity;
use Repeka\Domain\Entity\ResourceWorkflow;
use Repeka\Domain\Entity\User;
use Repeka\Domain\Workflow\TransitionPossibilityChecker;
use Repeka\Domain\Workflow\TransitionPossibilityCheckResult;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ResourceNormalizer extends AbstractNormalizer implements NormalizerAwareInterface {
    use NormalizerAwareTrait;

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var TransitionPossibilityChecker */
    private $transitionPossibilityChecker;

    public function __construct(TokenStorageInterface $tokenStorage, TransitionPossibilityChecker $transitionPossibilityChecker) {
        $this->tokenStorage = $tokenStorage;
        $this->transitionPossibilityChecker = $transitionPossibilityChecker;
    }

    /**
     * @param $resource ResourceEntity
     * @inheritdoc
     */
    public function normalize($resource, $format = null, array $context = []) {
        $normalized = [
            'id' => $resource->getId(),
            'kindId' => $resource->getKind()->getId(),
            'contents' => $resource->getContents(),
            'resourceClass' => $resource->getResourceClass(),
        ];
        if ($resource->hasWorkflow()) {
            $workflow = $resource->getWorkflow();
            $normalizerFunc = [$this->normalizer, 'normalize'];
            $normalized['currentPlaces'] = array_map($normalizerFunc, $workflow->getPlaces($resource));
            $normalized['availableTransitions'] = array_map($normalizerFunc, $workflow->getTransitions($resource));
            $normalized['blockedTransitions'] = array_map(
                $normalizerFunc,
                $this->getBlockedTransitions($resource, $this->tokenStorage->getToken()->getUser())
            );
            $normalized['transitionAssigneeMetadata'] = $this->getTransitionAssigneeMetadata($workflow, $resource);
        }
        return $normalized;
    }

    /** @return TransitionPossibilityCheckResult[] */
    private function getBlockedTransitions(ResourceEntity $resource, User $currentUser): array {
        /** @var TransitionPossibilityCheckResult */
        $failedPossibilityChecks = [];
        foreach ($resource->getWorkflow()->getTransitions($resource) as $transition) {
            $result = $this->transitionPossibilityChecker->check($resource, $transition, $currentUser);
            if (!$result->isTransitionPossible()) {
                $failedPossibilityChecks[$transition->getId()] = $result;
            }
        }
        return $failedPossibilityChecks;
    }

    private function getTransitionAssigneeMetadata(ResourceWorkflow $workflow, ResourceEntity $resource): array {
        $metadataTransitionMap = [];
        foreach ($workflow->getTransitions($resource) as $transition) {
            $assigneeMetadataIds = $this->transitionPossibilityChecker->getAssigneeMetadataIds($workflow, $transition);
            foreach ($assigneeMetadataIds as $metadataId) {
                if (!array_key_exists($metadataId, $metadataTransitionMap)) {
                    $metadataTransitionMap[$metadataId] = [];
                }
                $metadataTransitionMap[$metadataId][] = $this->normalizer->normalize($transition);
            }
        }
        return $metadataTransitionMap;
    }

    /** @inheritdoc */
    public function supportsNormalization($data, $format = null) {
        return $data instanceof ResourceEntity;
    }
}
