<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\UpdateRoleCommand;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;
use InvalidArgumentException;

final class UpdateRoleHandler implements CommandHandlerInterface
{
  public function __construct(private RoleRepositoryInterface $roles)
  {
  }

  public function commandClass(): string
  {
    return UpdateRoleCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof UpdateRoleCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $role = AccessControlLookup::role($this->roles, $command->roleId);
    $name = RoleName::fromString($command->name);
    AccessControlLookup::ensureRoleNameIsAvailable($this->roles, $name, $role->id());

    $role->rename($name);
    $role->describe($command->description);
    $this->roles->save($role);
  }
}
