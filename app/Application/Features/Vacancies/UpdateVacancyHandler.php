<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use InvalidArgumentException;

final class UpdateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
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
    $vacancy->revise(
      VacancyTitle::fromString($command->title),
      VacancyDescription::fromString($command->description)
    );
    $vacancy->changeStatus(VacancyStatus::fromBool($command->active));

    $this->vacancies->save($vacancy);
  }
}
