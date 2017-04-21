<?php
namespace Repeka\Application\EventListener;

use Repeka\Application\Entity\UserEntity;
use Repeka\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GlobalExceptionHandler {
    use TargetPathTrait;

    const FIREWALL_NAME = 'main';
    const ADMIN_PANEL_PREFIX = '/admin/';

    private $isDebug;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct($isDebug, TokenStorage $tokenStorage, SessionInterface $session) {
        $this->isDebug = $isDebug;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
    }

    public function onException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();
        $request = $event->getRequest();
        $errorResponse = $this->createErrorResponse($exception, $request);
        $event->setResponse($errorResponse);
    }

    public function createErrorResponse(\Exception $e, Request $request) {
        if ($e instanceof AuthenticationException || $e instanceof AccessDeniedException) {
            return $this->buildResponseForXmlHttpRequest($request);
        } elseif ($e instanceof DomainException) {
            return $this->createJSONResponse($e->getCode(), $e->getMessage());
        } else {
            $message = $this->isDebug ? $e->getMessage() : 'Internal server error.';
            return $this->createJSONResponse(500, $message);
        }
    }

    private function buildResponseForXmlHttpRequest(Request $request) {
        if ($this->tokenStorage->getToken()->getUser() instanceof UserEntity) {
            return $this->createJSONResponse(403, 'Forbidden');
        } else {
            $this->saveTargetUrlIfFromAdminPanel($request);
            return $this->createJSONResponse(401, 'Unauthorized');
        }
    }

    private function createJSONResponse($status, $message): JsonResponse {
        return new JsonResponse([
            'message' => $message
        ], $status);
    }

    private function saveTargetUrlIfFromAdminPanel(Request $request) {
        $callingAddress = $request->headers->get('referer');
        if ($offset = strpos($callingAddress, self::ADMIN_PANEL_PREFIX)) {
            $path = substr($callingAddress, $offset);
            $this->saveTargetPath($this->session, self::FIREWALL_NAME, $path);
        }
    }
}
