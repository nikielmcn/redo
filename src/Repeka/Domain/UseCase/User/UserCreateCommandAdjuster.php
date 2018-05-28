<?php
namespace Repeka\Domain\UseCase\User;

use Repeka\Domain\Cqrs\Command;
use Repeka\Domain\Cqrs\CommandAdjuster;

class UserCreateCommandAdjuster implements CommandAdjuster {
    /** @param UserCreateCommand $command */
    public function adjustCommand(Command $command): Command {
        return new UserCreateCommand(
            strtolower($command->getUsername()),
            $command->getPlainPassword(),
            $command->getUserData()
        );
    }
}
