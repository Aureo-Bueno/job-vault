<?php

namespace App\Application\Features\Applications;

use App\Application\DTOs\ApplicationEventDto;
use App\Application\DTOs\ApplicationNoteDto;
use App\Application\Exceptions\NotFoundException;
use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationId;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;

/**
 * Shared helpers for the application (pipeline) use cases.
 */
final class ApplicationViews
{
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var array<string,string> */
  private array $names = [];

  public function __construct(private UserRepositoryInterface $users)
  {
  }

  public static function load(JobApplicationRepositoryInterface $applications, string $id): JobApplication
  {
    $application = JobApplicationId::isValid($id) ? $applications->get(JobApplicationId::fromString($id)) : null;
    if ($application === null) {
      throw new NotFoundException('Candidatura não encontrada.');
    }

    return $application;
  }

  /**
   * Full history for the hiring team, with who made each change.
   *
   * @return list<ApplicationEventDto>
   */
  public function recruiterHistory(JobApplication $application): array
  {
    return array_map(
      fn ($event): ApplicationEventDto => new ApplicationEventDto(
        $event->to->value,
        $event->to->label(),
        $event->actorId !== null && $event->actorId->equals($application->candidateId())
          ? 'Candidato'
          : $this->nameOf($event->actorId),
        $event->reason ?? '',
        $event->at->format(self::DATE_FORMAT)
      ),
      $application->history()
    );
  }

  /**
   * History shown to the candidate: stages and rejection feedback, never who moved it.
   *
   * @return list<ApplicationEventDto>
   */
  public static function candidateTimeline(JobApplication $application): array
  {
    return array_map(
      static fn ($event): ApplicationEventDto => new ApplicationEventDto(
        $event->to->value,
        $event->to->candidateLabel(),
        '',
        $event->to === ApplicationStatus::Rejected ? ($event->reason ?? '') : '',
        $event->at->format(self::DATE_FORMAT)
      ),
      $application->history()
    );
  }

  /**
   * @param list<ApplicationNote> $notes
   * @return list<ApplicationNoteDto>
   */
  public function notes(array $notes): array
  {
    return array_map(
      fn (ApplicationNote $note): ApplicationNoteDto => new ApplicationNoteDto(
        $this->nameOf($note->authorId),
        $note->body,
        $note->createdAt->format(self::DATE_FORMAT)
      ),
      $notes
    );
  }

  private function nameOf(?UserId $userId): string
  {
    if ($userId === null) {
      return 'Usuário removido';
    }

    return $this->names[$userId->value()] ??= $this->users->get($userId)?->name()->value() ?? 'Usuário removido';
  }
}
