<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Service\VacancyService;
use InvalidArgumentException;

/**
 * Handles vacancy update command.
 */
final class UpdateVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function commandClass(): string
  {
    return UpdateVacancyCommand::class;
  }

  public function handle(Command $command): bool
  {
    if (!$command instanceof UpdateVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for UpdateVacancyHandler.');
    }

    $vacancy = $this->vacancyService->getById($command->vacancyId);
    if (!$vacancy) {
      return false;
    }

    $vacancy->title = $command->title;
    $vacancy->description = $command->description;
    $vacancy->isActive = $command->isActive;

    return $this->vacancyService->update($vacancy);
  }
}
