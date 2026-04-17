<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Mappings\UserMapping;
use App\Application\Service\UserService;
use App\Domain\Model\User;
use InvalidArgumentException;

/**
 * Handles user creation command.
 */
final class CreateUserHandler implements CommandHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function commandClass(): string
  {
    return CreateUserCommand::class;
  }

  public function handle(Command $command): ?UserDto
  {
    if (!$command instanceof CreateUserCommand) {
      throw new InvalidArgumentException('Invalid command for CreateUserHandler.');
    }

    $user = new User();
    $user->name = $command->name;
    $user->email = $command->email;
    $user->roleId = $command->roleId;

    $created = $this->userService->create($user, $command->password);

    return $created ? UserMapping::toDto($created) : null;
  }
}
