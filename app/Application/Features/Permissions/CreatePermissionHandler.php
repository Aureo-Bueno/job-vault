<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\Mappings\PermissionMapping;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles permission creation command.
 */
final class CreatePermissionHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return CreatePermissionCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof CreatePermissionCommand) {
      throw new InvalidArgumentException('Invalid command for CreatePermissionHandler.');
    }

    $result = $this->accessControlService->createPermission(
      $command->name,
      $command->description,
      $command->module,
      $command->action
    );

    $permission = $result['permission'] ?? null;

    return [
      'ok' => $result['ok'] ?? false,
      'error' => $result['error'] ?? null,
      'permission' => $permission ? PermissionMapping::toDto($permission) : null,
    ];
  }
}
