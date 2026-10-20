<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\DTOs\RoleDto;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;
use InvalidArgumentException;

final class CreateRoleHandler implements CommandHandlerInterface
{
  public function __construct(private RoleRepositoryInterface $roles)
  {
  }

  public function commandClass(): string
  {
    return CreateRoleCommand::class;
  }

  public function handle(Command $command): RoleDto
  {
    if (!$command instanceof CreateRoleCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $name = RoleName::fromString($command->name);
    AccessControlLookup::ensureRoleNameIsAvailable($this->roles, $name);

    $role = Role::create(RoleId::generate(), $name, $command->description);
    $this->roles->save($role);

    return RoleDto::fromRole($role);
  }
}
