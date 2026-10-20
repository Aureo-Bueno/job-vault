<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Users\UserRegistrar;
use InvalidArgumentException;

final class CreateUserHandler implements CommandHandlerInterface
{
  public function __construct(private UserRegistrar $registrar)
  {
  }

  public function commandClass(): string
  {
    return CreateUserCommand::class;
  }

  public function handle(Command $command): UserDto
  {
    if (!$command instanceof CreateUserCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $user = $this->registrar->register(
      $command->name,
      $command->email,
      $command->password,
      $command->roleId,
      $command->companyId
    );

    return UserDto::fromUser($user);
  }
}
