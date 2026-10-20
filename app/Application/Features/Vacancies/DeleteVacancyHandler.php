<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class DeleteVacancyHandler implements CommandHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private ?VacancyAccessScope $scope = null
  ) {
  }

  public function commandClass(): string
  {
    return DeleteVacancyCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof DeleteVacancyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $vacancy = VacancyLoader::load($this->vacancies, $command->vacancyId);
    ($this->scope ?? new VacancyAccessScope())->assertCanManage($vacancy);

    $this->vacancies->remove($vacancy->id());
  }
}
