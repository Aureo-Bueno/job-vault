<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user deletion command.
 */
final class DeleteUserHandler implements CommandHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function commandClass(): string
  {
    return DeleteUserCommand::class;
  }

  public function handle(Command $command): bool
  {
    if (!$command instanceof DeleteUserCommand) {
      throw new InvalidArgumentException('Invalid command for DeleteUserHandler.');
    }

    return $this->userService->delete($command->userId);
  }
}
