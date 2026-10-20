<?php

namespace App\Application\Features\RolePermissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Features\Roles\AccessControlLookup;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\RoleRepositoryInterface;
use InvalidArgumentException;

final class AssignPermissionToRoleHandler implements CommandHandlerInterface
{
  public function __construct(
    private RoleRepositoryInterface $roles,
    private PermissionRepositoryInterface $permissions
  ) {
  }

  public function commandClass(): string
  {
    return AssignPermissionToRoleCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof AssignPermissionToRoleCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $role = AccessControlLookup::role($this->roles, $command->roleId);
    $permission = AccessControlLookup::permission($this->permissions, $command->permissionId);

    $role->grant($permission->id());
    $this->roles->save($role);
  }
}
