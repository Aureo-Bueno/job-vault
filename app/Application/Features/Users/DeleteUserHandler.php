<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Exceptions\NotFoundException;
use App\Application\Security\CurrentUserProvider;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class DeleteUserHandler implements CommandHandlerInterface
{
  public function __construct(
    private UserRepositoryInterface $users,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return DeleteUserCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof DeleteUserCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $userId = UserId::fromString($command->userId);
    if ($this->currentUser->currentUser()?->id === $userId->value()) {
      throw new BusinessRuleViolationException('Não é permitido excluir o próprio usuário logado.');
    }

    if ($this->users->get($userId) === null) {
      throw new NotFoundException('Usuário não encontrado.');
    }

    $this->users->remove($userId);
  }
}
