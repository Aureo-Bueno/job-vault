<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\VacancyDeadline;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyPositions;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use InvalidArgumentException;

final class UpdateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private ?VacancyAccessScope $scope = null
  ) {
  }

  public function commandClass(): string
  {
    return UpdateVacancyCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof UpdateVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $vacancy = VacancyLoader::load($this->vacancies, $command->vacancyId);
    $scope = $this->scope ?? new VacancyAccessScope();
    $scope->assertCanManage($vacancy);

    $vacancy->revise(
      VacancyTitle::fromString($command->title),
      VacancyDescription::fromString($command->description),
      $command->area !== null ? WorkArea::fromString($command->area) : null,
      $command->seniority !== null ? Seniority::fromString($command->seniority) : null,
      $command->workMode !== null ? WorkMode::fromString($command->workMode) : null,
      $command->topics,
      $command->positions !== null ? VacancyPositions::fromInt($command->positions) : null,
      $command->contractType !== null
        ? EmploymentTermsInput::toTerms(
          $command->contractType,
          $command->salaryMin,
          $command->salaryMax,
          $command->city,
          $command->state,
          $command->benefits
        )
        : null
    );
    if ($command->closesAt !== null) {
      $vacancy->reschedule(VacancyDeadline::fromInput($command->closesAt));
    }
    // Last: reopening checks the (possibly new) deadline.
    if ($command->status !== null) {
      $vacancy->changeStatus(VacancyStatus::fromString($command->status));
    }

    if ($command->changeCompany && $scope->isAdmin()) {
      $vacancy->assignCompany($scope->resolveAssignedCompany($command->companyId));
    }

    $this->vacancies->save($vacancy);
  }
}
