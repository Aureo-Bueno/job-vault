<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Applications\AddApplicationNoteCommand;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Features\Vacancies\VacancyLoader;
use App\Application\Security\CurrentUserProvider;
use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

/**
 * Recruiter writes an internal note on an application of one of their company's vacancies.
 */
final class AddApplicationNoteHandler implements CommandHandlerInterface
{
  public function __construct(
    private JobApplicationRepositoryInterface $applications,
    private VacancyRepositoryInterface $vacancies,
    private VacancyAccessScope $scope,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return AddApplicationNoteCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof AddApplicationNoteCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $author = $this->currentUser->currentUser();
    if ($author === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $application = ApplicationViews::load($this->applications, $command->applicationId);
    $this->scope->assertCanManage(VacancyLoader::load($this->vacancies, $application->vacancyId()->value()));

    $this->applications->addNote(ApplicationNote::write($application->id(), UserId::fromString($author->id), $command->body));
  }
}
