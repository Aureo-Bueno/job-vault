<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\CloseExpiredVacanciesCommand;
use App\Application\DTOs\VacancyDto;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class CloseExpiredVacanciesHandler implements CommandHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
  }

  public function commandClass(): string
  {
    return CloseExpiredVacanciesCommand::class;
  }

  /**
   * @return list<VacancyDto> the vacancies closed by this run
   */
  public function handle(Command $command): array
  {
    if (!$command instanceof CloseExpiredVacanciesCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $now = $command->now ?? new DateTimeImmutable();
    $closed = [];
    foreach ($this->vacancies->openPastDeadline($now) as $vacancy) {
      if ($vacancy->closeIfExpired($now)) {
        $this->vacancies->save($vacancy);
        $closed[] = VacancyDto::fromVacancy($vacancy);
      }
    }

    return $closed;
  }
}
