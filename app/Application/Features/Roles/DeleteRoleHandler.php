<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\DeleteRoleCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role deletion command.
 */
final class DeleteRoleHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return DeleteRoleCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof DeleteRoleCommand) {
      throw new InvalidArgumentException('Invalid command for DeleteRoleHandler.');
    }

    return $this->accessControlService->deleteRole($command->roleId);
  }
}
