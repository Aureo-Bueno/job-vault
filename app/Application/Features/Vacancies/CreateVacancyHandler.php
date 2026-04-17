<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\DTOs\VacancyDto;
use App\Application\Mappings\VacancyMapping;
use App\Application\Service\VacancyService;
use App\Domain\Model\Vacancy;
use InvalidArgumentException;

/**
 * Handles vacancy creation command.
 */
final class CreateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function commandClass(): string
  {
    return CreateVacancyCommand::class;
  }

  public function handle(Command $command): VacancyDto
  {
    if (!$command instanceof CreateVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for CreateVacancyHandler.');
    }

    $vacancy = new Vacancy();
    $vacancy->title = $command->title;
    $vacancy->description = $command->description;
    $vacancy->isActive = $command->isActive;

    $created = $this->vacancyService->create($vacancy);

    return VacancyMapping::toDto($created);
  }
}
