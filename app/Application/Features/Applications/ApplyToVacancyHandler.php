<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\DTOs\ApplicationResultDto;
use App\Application\Service\ApplicationService;
use InvalidArgumentException;

/**
 * Handles user application to a vacancy.
 */
final class ApplyToVacancyHandler implements CommandHandlerInterface
{
  public function __construct(private ApplicationService $applicationService)
  {
  }

  public function commandClass(): string
  {
    return ApplyToVacancyCommand::class;
  }

  public function handle(Command $command): ApplicationResultDto
  {
    if (!$command instanceof ApplyToVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for ApplyToVacancyHandler.');
    }

    $status = $this->applicationService->apply($command->userId, $command->vacancyId);

    return new ApplicationResultDto(
      $status,
      $command->userId,
      $command->vacancyId
    );
  }
}
