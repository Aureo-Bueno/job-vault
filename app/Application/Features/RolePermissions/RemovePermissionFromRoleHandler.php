<?php

namespace App\Application\Features\RolePermissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Features\Roles\AccessControlLookup;
use App\Domain\Permission\PermissionId;
use App\Domain\Role\RoleRepositoryInterface;
use InvalidArgumentException;

final class RemovePermissionFromRoleHandler implements CommandHandlerInterface
{
  public function __construct(private RoleRepositoryInterface $roles)
  {
  }

  public function commandClass(): string
  {
    return RemovePermissionFromRoleCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof RemovePermissionFromRoleCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $role = AccessControlLookup::role($this->roles, $command->roleId);
    $role->revoke(PermissionId::fromString($command->permissionId));
    $this->roles->save($role);
  }
}
