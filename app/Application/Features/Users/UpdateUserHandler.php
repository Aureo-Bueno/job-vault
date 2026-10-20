<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Exceptions\NotFoundException;
use App\Application\Users\UserRegistrar;
use App\Domain\User\EmailAddress;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class UpdateUserHandler implements CommandHandlerInterface
{
  public function __construct(
    private UserRepositoryInterface $users,
    private UserRegistrar $registrar
  ) {
  }

  public function commandClass(): string
  {
    return UpdateUserCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof UpdateUserCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $user = $this->users->get(UserId::fromString($command->userId));
    if ($user === null) {
      throw new NotFoundException('Usuário não encontrado.');
    }

    $email = EmailAddress::fromString($command->email);
    $this->registrar->ensureEmailIsAvailable($email, $user->id());
    $user->changeProfile(PersonName::fromString($command->name), $email);

    if ($command->password !== null && $command->password !== '') {
      $user->changePassword(PlainPassword::fromString($command->password));
    }

    if ($command->changeRole) {
      $roleId = $command->roleId;
      $user->assignRole($roleId === null || $roleId === '' ? null : $this->registrar->existingRole($roleId));
    }

    $this->users->save($user);
  }
}
