<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Security\CurrentUserProvider;
use App\Domain\JobApplication\AlreadyAppliedException;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

/**
 * The current user applies to an open vacancy (once; again only after withdrawing).
 */
final class ApplyToVacancyHandler implements CommandHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private JobApplicationRepositoryInterface $applications,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return ApplyToVacancyCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof ApplyToVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $current = $this->currentUser->currentUser();
    if ($current === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $candidateId = UserId::fromString($current->id);
    $vacancy = $this->vacancies->get(VacancyId::fromString($command->vacancyId));
    if ($vacancy === null) {
      throw new NotFoundException('Vaga não encontrada.');
    }

    $existing = $this->applications->findFor($candidateId, $vacancy->id());
    if ($existing === null) {
      $this->applications->save($vacancy->receiveApplicationFrom($candidateId));
      return;
    }

    if ($existing->status() !== ApplicationStatus::Withdrawn) {
      throw new AlreadyAppliedException();
    }

    $vacancy->acceptResubmission($existing, $candidateId);
    $this->applications->save($existing);
  }
}
