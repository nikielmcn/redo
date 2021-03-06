<?php
namespace Repeka\Application\Controller\Api;

use Assert\Assertion;
use Repeka\Domain\Entity\ResourceKind;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindCreateCommand;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindDeleteCommand;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindListQuery;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindQuery;
use Repeka\Domain\UseCase\ResourceKind\ResourceKindUpdateCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/resource-kinds")
 */
class ResourceKindsController extends ApiController {
    /**
     * @Route
     * @Method("GET")
     */
    public function getListAction(Request $request) {
        $resourceClasses = $request->query->get('resourceClasses', []);
        $ids = $request->query->get('ids', []);
        $metadataId = $request->query->get('metadataId', 0);
        $sortByIds = $request->query->get('sortByIds', []);
        Assertion::isArray($resourceClasses);
        Assertion::isArray($ids);
        Assertion::isArray($sortByIds);
        $resourceKindListQueryBuilder = ResourceKindListQuery::builder()
            ->filterByResourceClasses($resourceClasses)
            ->filterByMetadataId($metadataId)
            ->sortBy($sortByIds)
            ->filterByIds($ids);
        $resourceKindListQuery = $resourceKindListQueryBuilder->build();
        $resourceKindList = $this->handleCommand($resourceKindListQuery);
        return $this->createJsonResponse($resourceKindList);
    }

    /**
     * @Route("/{id}")
     * @Method("GET")
     */
    public function getAction(string $id) {
        $resourceKind = $this->handleCommand(new ResourceKindQuery(intval($id)));
        return $this->createJsonResponse($resourceKind);
    }

    /**
     * @Route
     * @Method("POST")
     */
    public function postAction(Request $request) {
        $data = $request->request->all();
        $command = new ResourceKindCreateCommand(
            $data['label'] ?? [],
            $data['metadataList'] ?? [],
            $data['workflowId'] ?? null
        );
        $resourceKind = $this->handleCommand($command);
        return $this->createJsonResponse($resourceKind, 201);
    }

    /**
     * @Route("/{id}")
     * @Method("PUT")
     */
    public function putAction(ResourceKind $resourceKind, Request $request) {
        $data = $request->request->all();
        Assertion::keyExists($data, 'label');
        Assertion::keyExists($data, 'metadataList');
        $command = new ResourceKindUpdateCommand(
            $resourceKind,
            $data['label'],
            $data['metadataList'],
            $data['workflowId'] ?? null
        );
        $resourceKind = $this->handleCommand($command);
        return $this->createJsonResponse($resourceKind);
    }

    /**
     * @Route("/{id}")
     * @Method("DELETE")
     */
    public function deleteAction(ResourceKind $resourceKind) {
        $this->handleCommand(new ResourceKindDeleteCommand($resourceKind));
        return new Response('', 204);
    }
}
