<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\UpdatePermissionCommand;
use App\Application\Features\Roles\AccessControlLookup;
use App\Domain\Permission\PermissionRepositoryInterface;
use InvalidArgumentException;

final class UpdatePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private PermissionRepositoryInterface $permissions)
  {
  }

  public function commandClass(): string
  {
    return UpdatePermissionCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof UpdatePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $permission = AccessControlLookup::permission($this->permissions, $command->permissionId);
    $permission->redefine($command->name, $command->description, $command->module, $command->action);
    AccessControlLookup::ensurePermissionNameIsAvailable($this->permissions, $permission->name(), $permission->id());

    $this->permissions->save($permission);
  }
}
