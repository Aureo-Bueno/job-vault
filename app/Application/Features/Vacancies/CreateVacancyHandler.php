<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\DTOs\VacancyDto;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use InvalidArgumentException;

final class CreateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private ?VacancyAccessScope $scope = null,
    private ?CompanyRepositoryInterface $companies = null
  ) {
  }

  public function commandClass(): string
  {
    return CreateVacancyCommand::class;
  }

  public function handle(Command $command): VacancyDto
  {
    if (!$command instanceof CreateVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $scope = $this->scope ?? new VacancyAccessScope(companies: $this->companies);
    $companyId = $scope->resolveAssignedCompany($command->companyId);

    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString($command->title),
      VacancyDescription::fromString($command->description),
      VacancyStatus::fromBool($command->active),
      null,
      $companyId,
      WorkArea::fromString($command->area),
      Seniority::fromString($command->seniority),
      WorkMode::fromString($command->workMode),
      $command->topics
    );
    $this->vacancies->save($vacancy);

    $company = ($companyId !== null && $this->companies !== null) ? $this->companies->get($companyId) : null;
    return VacancyDto::fromVacancy($vacancy, $company);
  }
}
