<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Service\VacancyService;
use InvalidArgumentException;

/**
 * Handles vacancy deletion command.
 */
final class DeleteVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function commandClass(): string
  {
    return DeleteVacancyCommand::class;
  }

  public function handle(Command $command): bool
  {
    if (!$command instanceof DeleteVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for DeleteVacancyHandler.');
    }

    return $this->vacancyService->delete($command->vacancyId);
  }
}
