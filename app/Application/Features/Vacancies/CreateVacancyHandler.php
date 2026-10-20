<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\DTOs\VacancyDto;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use InvalidArgumentException;

final class CreateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
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

    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString($command->title),
      VacancyDescription::fromString($command->description),
      VacancyStatus::fromBool($command->active)
    );
    $this->vacancies->save($vacancy);

    return VacancyDto::fromVacancy($vacancy);
  }
}
