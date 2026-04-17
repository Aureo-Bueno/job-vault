<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user update command.
 */
final class UpdateUserHandler implements CommandHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function commandClass(): string
  {
    return UpdateUserCommand::class;
  }

  public function handle(Command $command): bool
  {
    if (!$command instanceof UpdateUserCommand) {
      throw new InvalidArgumentException('Invalid command for UpdateUserHandler.');
    }

    $user = $this->userService->getById($command->userId);
    if (!$user) {
      return false;
    }

    $user->name = $command->name;
    $user->email = $command->email;
    $user->roleId = $command->roleId;

    return $this->userService->update($user, $command->password);
  }
}
