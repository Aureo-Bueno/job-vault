<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\UpdateRoleCommand;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role update command.
 */
final class UpdateRoleHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return UpdateRoleCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof UpdateRoleCommand) {
      throw new InvalidArgumentException('Invalid command for UpdateRoleHandler.');
    }

    return $this->accessControlService->updateRole(
      $command->roleId,
      $command->name,
      $command->description
    );
  }
}
