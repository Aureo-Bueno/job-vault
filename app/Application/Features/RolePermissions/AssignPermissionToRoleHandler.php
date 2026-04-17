<?php

namespace App\Application\Features\RolePermissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role-permission assignment command.
 */
final class AssignPermissionToRoleHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return AssignPermissionToRoleCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof AssignPermissionToRoleCommand) {
      throw new InvalidArgumentException('Invalid command for AssignPermissionToRoleHandler.');
    }

    return $this->accessControlService->assignPermissionToRole(
      $command->roleId,
      $command->permissionId
    );
  }
}
