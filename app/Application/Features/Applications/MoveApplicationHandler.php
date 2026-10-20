<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Applications\MoveApplicationCommand;
use App\Application\DTOs\MoveApplicationResultDto;
use App\Application\Exceptions\ConflictException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Features\Vacancies\VacancyLoader;
use App\Application\Security\CurrentUserProvider;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

/**
 * Recruiter advances, rejects or reopens an application of one of their company's vacancies.
 * Approving the candidate that fills the last position closes the vacancy.
 */
final class MoveApplicationHandler implements CommandHandlerInterface
{
  public function __construct(
    private JobApplicationRepositoryInterface $applications,
    private VacancyRepositoryInterface $vacancies,
    private VacancyAccessScope $scope,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return MoveApplicationCommand::class;
  }

  public function handle(Command $command): MoveApplicationResultDto
  {
    if (!$command instanceof MoveApplicationCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $actor = $this->currentUser->currentUser();
    if ($actor === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $application = ApplicationViews::load($this->applications, $command->applicationId);
    $vacancy = VacancyLoader::load($this->vacancies, $application->vacancyId()->value());
    $this->scope->assertCanManage($vacancy);

    if ($command->expectedStatus !== '' && $command->expectedStatus !== $application->status()->value) {
      throw new ConflictException('Esta candidatura já foi movida por outra pessoa. O quadro foi atualizado.');
    }

    $actorId = UserId::fromString($actor->id);
    match ($command->action) {
      MoveApplicationCommand::ADVANCE => $application->advance($actorId),
      MoveApplicationCommand::REJECT => $application->reject($actorId, $command->feedback),
      MoveApplicationCommand::REOPEN => $application->reopen($actorId),
      default => throw new MessageValidationException('Ação inválida para a candidatura.'),
    };

    $this->applications->save($application);

    $closed = false;
    if ($application->status() === ApplicationStatus::Approved) {
      $closed = $vacancy->closeIfFilled($this->applications->approvedCountFor($vacancy->id()));
      if ($closed) {
        $this->vacancies->save($vacancy);
      }
    }

    return new MoveApplicationResultDto($application->status()->value, $application->status()->label(), $closed);
  }
}
