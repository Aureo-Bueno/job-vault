<?php

namespace App\Application\Features\RolePermissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role-permission removal command.
 */
final class RemovePermissionFromRoleHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return RemovePermissionFromRoleCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof RemovePermissionFromRoleCommand) {
      throw new InvalidArgumentException('Invalid command for RemovePermissionFromRoleHandler.');
    }

    return $this->accessControlService->removePermissionFromRole(
      $command->roleId,
      $command->permissionId
    );
  }
}
