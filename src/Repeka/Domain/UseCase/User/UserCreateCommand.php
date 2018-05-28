<?php
namespace Repeka\Domain\UseCase\User;

use Repeka\Domain\Cqrs\AbstractCommand;
use Repeka\Domain\Cqrs\AdjustableCommand;
use Repeka\Domain\Entity\ResourceContents;

class UserCreateCommand extends AbstractCommand implements AdjustableCommand {
    /** @var string */
    private $username;
    /** @var null|string */
    private $plainPassword;
    /** @var array */
    private $userData;

    public function __construct(string $username, string $plainPassword = null, ResourceContents $userData = null) {
        $this->username = $username;
        $this->plainPassword = $plainPassword;
        $this->userData = $userData ?: ResourceContents::empty();
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getUserData(): ResourceContents {
        return $this->userData;
    }

    public function getPlainPassword(): ?string {
        return $this->plainPassword;
    }
}
