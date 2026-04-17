<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\Mappings\RoleMapping;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role creation command.
 */
final class CreateRoleHandler implements CommandHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function commandClass(): string
  {
    return CreateRoleCommand::class;
  }

  public function handle(Command $command): array
  {
    if (!$command instanceof CreateRoleCommand) {
      throw new InvalidArgumentException('Invalid command for CreateRoleHandler.');
    }

    $result = $this->accessControlService->createRole($command->name, $command->description);
    $role = $result['role'] ?? null;

    return [
      'ok' => $result['ok'] ?? false,
      'error' => $result['error'] ?? null,
      'role' => $role ? RoleMapping::toDto($role) : null,
    ];
  }
}
