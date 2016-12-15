<?php
namespace Repeka\Application\Controller\Api;

use Repeka\Domain\Cqrs\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends Controller {
    /** @var CommandBus */
    protected $commandBus;

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
        $this->commandBus = $this->get('repeka.command_bus');
    }

    protected function createJsonResponse($data, $status = 200): JsonResponse {
        $json = $this->get('jms_serializer')->serialize($data, 'json');
        $response = new JsonResponse();
        $response->setJson($json);
        $response->setStatusCode($status);
        return $response;
    }
}