<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\UpdatePermissionCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles permission update command.
 */
final class UpdatePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return UpdatePermissionCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof UpdatePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for UpdatePermissionHandler.');
    }

    return $this->accessControlService->updatePermission(
      $command->permissionId,
      $command->name,
      $command->description,
      $command->module,
      $command->action
    );
  }
}
