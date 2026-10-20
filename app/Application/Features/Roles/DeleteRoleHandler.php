<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\DeleteRoleCommand;
use App\Domain\Role\RoleRepositoryInterface;
use App\Domain\User\UserFilter;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class DeleteRoleHandler implements CommandHandlerInterface
{
  public function __construct(
    private RoleRepositoryInterface $roles,
    private UserRepositoryInterface $users
  ) {
  }

  public function commandClass(): string
  {
    return DeleteRoleCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof DeleteRoleCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $role = AccessControlLookup::role($this->roles, $command->roleId);
    $role->ensureCanBeDeleted($this->users->countMatching(new UserFilter(null, $role->id())));

    $this->roles->remove($role->id());
  }
}
