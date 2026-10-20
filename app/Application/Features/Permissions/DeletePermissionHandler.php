<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\DeletePermissionCommand;
use App\Application\Features\Roles\AccessControlLookup;
use App\Domain\Permission\PermissionRepositoryInterface;
use InvalidArgumentException;

final class DeletePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private PermissionRepositoryInterface $permissions)
  {
  }

  public function commandClass(): string
  {
    return DeletePermissionCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof DeletePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $permission = AccessControlLookup::permission($this->permissions, $command->permissionId);
    $this->permissions->remove($permission->id());
  }
}
