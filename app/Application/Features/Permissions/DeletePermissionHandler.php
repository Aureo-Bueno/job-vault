<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\DeletePermissionCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles permission deletion command.
 */
final class DeletePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return DeletePermissionCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof DeletePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for DeletePermissionHandler.');
    }

    return $this->accessControlService->deletePermission($command->permissionId);
  }
}
