<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Applications\WithdrawApplicationCommand;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Security\CurrentUserProvider;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use InvalidArgumentException;

/**
 * The candidate withdraws one of their own applications while it is still in process.
 */
final class WithdrawApplicationHandler implements CommandHandlerInterface
{
  public function __construct(
    private JobApplicationRepositoryInterface $applications,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return WithdrawApplicationCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof WithdrawApplicationCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $current = $this->currentUser->currentUser();
    if ($current === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $candidateId = UserId::fromString($current->id);
    $application = ApplicationViews::load($this->applications, $command->applicationId);
    if (!$application->candidateId()->equals($candidateId)) {
      // Do not reveal that someone else's application exists.
      throw new NotFoundException('Candidatura não encontrada.');
    }

    $application->withdraw($candidateId);
    $this->applications->save($application);
  }
}
