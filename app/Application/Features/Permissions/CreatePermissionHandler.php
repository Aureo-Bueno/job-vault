<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\DTOs\PermissionDto;
use App\Application\Features\Roles\AccessControlLookup;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Permission\PermissionRepositoryInterface;
use InvalidArgumentException;

final class CreatePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private PermissionRepositoryInterface $permissions)
  {
  }

  public function commandClass(): string
  {
    return CreatePermissionCommand::class;
  }

  public function handle(Command $command): PermissionDto
  {
    if (!$command instanceof CreatePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $permission = Permission::define(
      PermissionId::generate(),
      $command->name,
      $command->description,
      $command->module,
      $command->action
    );
    AccessControlLookup::ensurePermissionNameIsAvailable($this->permissions, $permission->name());

    $this->permissions->save($permission);

    return PermissionDto::fromPermission($permission);
  }
}
