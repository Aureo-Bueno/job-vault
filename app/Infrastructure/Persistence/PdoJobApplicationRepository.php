<?php

namespace App\Infrastructure\Persistence;

use App\Domain\JobApplication\ApplicationEvent;
use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationId;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

final class PdoJobApplicationRepository implements JobApplicationRepositoryInterface
{
  private const COLUMNS = 'id, user_id, vacancy_id, status, status_changed_at, candidate_feedback, created_at';
  private const WITHDRAWN = 'desistiu';

  public function __construct(private Database $db)
  {
  }

  public function get(JobApplicationId $id): ?JobApplication
  {
    return $this->hydrate($this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM applications WHERE id = ?', [$id->value()]))[0] ?? null;
  }

  public function findFor(UserId $candidateId, VacancyId $vacancyId): ?JobApplication
  {
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM applications WHERE user_id = ? AND vacancy_id = ? LIMIT 1',
      [$candidateId->value(), $vacancyId->value()]
    );

    return $this->hydrate($rows)[0] ?? null;
  }

  public function exists(UserId $candidateId, VacancyId $vacancyId): bool
  {
    return (bool) $this->db->fetchValue(
      'SELECT 1 FROM applications WHERE user_id = ? AND vacancy_id = ? LIMIT 1',
      [$candidateId->value(), $vacancyId->value()]
    );
  }

  public function save(JobApplication $application): void
  {
    $id = $application->id()->value();
    $values = [
      $application->status()->value,
      Sql::fromDate($application->statusChangedAt()),
      $application->candidateFeedback(),
      $id,
    ];

    if ($this->db->fetchValue('SELECT 1 FROM applications WHERE id = ?', [$id])) {
      $this->db->run('UPDATE applications SET status = ?, status_changed_at = ?, candidate_feedback = ? WHERE id = ?', $values);
    } else {
      $this->db->run(
        'INSERT INTO applications (status, status_changed_at, candidate_feedback, id, user_id, vacancy_id, created_at)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
          ...$values,
          $application->candidateId()->value(),
          $application->vacancyId()->value(),
          Sql::fromDate($application->submittedAt()),
        ]
      );
    }

    foreach ($application->releaseNewEvents() as $event) {
      $this->db->run(
        'INSERT INTO application_events (application_id, from_status, to_status, actor_id, reason, created_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$id, $event->from?->value, $event->to->value, $event->actorId?->value(), $event->reason, Sql::fromDate($event->at)]
      );
    }
  }

  public function vacancyIdsAppliedBy(UserId $candidateId): array
  {
    $rows = $this->db->fetchAll(
      'SELECT vacancy_id FROM applications WHERE user_id = ? AND status <> ?',
      [$candidateId->value(), self::WITHDRAWN]
    );

    return array_map(static fn (array $row): string => (string) $row['vacancy_id'], $rows);
  }

  public function forVacancy(VacancyId $vacancyId): array
  {
    return $this->hydrate($this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM applications WHERE vacancy_id = ? ORDER BY created_at DESC, id ASC',
      [$vacancyId->value()]
    ));
  }

  public function forCandidate(UserId $candidateId): array
  {
    return $this->hydrate($this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM applications WHERE user_id = ? ORDER BY status_changed_at DESC, id ASC',
      [$candidateId->value()]
    ));
  }

  public function countPerVacancy(): array
  {
    $rows = $this->db->fetchAll(
      'SELECT vacancy_id, COUNT(*) AS total FROM applications WHERE status <> ? GROUP BY vacancy_id',
      [self::WITHDRAWN]
    );

    $counts = [];
    foreach ($rows as $row) {
      $counts[(string) $row['vacancy_id']] = (int) $row['total'];
    }

    return $counts;
  }

  public function approvedPerVacancy(): array
  {
    $rows = $this->db->fetchAll(
      'SELECT vacancy_id, COUNT(*) AS total FROM applications WHERE status = ? GROUP BY vacancy_id',
      [ApplicationStatus::Approved->value]
    );

    $counts = [];
    foreach ($rows as $row) {
      $counts[(string) $row['vacancy_id']] = (int) $row['total'];
    }

    return $counts;
  }

  public function approvedCountFor(VacancyId $vacancyId): int
  {
    return (int) $this->db->fetchValue(
      'SELECT COUNT(*) FROM applications WHERE vacancy_id = ? AND status = ?',
      [$vacancyId->value(), ApplicationStatus::Approved->value]
    );
  }

  public function addNote(ApplicationNote $note): void
  {
    $this->db->run(
      'INSERT INTO application_notes (application_id, author_id, body, created_at) VALUES (?, ?, ?, ?)',
      [$note->applicationId->value(), $note->authorId?->value(), $note->body, Sql::fromDate($note->createdAt)]
    );
  }

  public function notesFor(array $applicationIds): array
  {
    if ($applicationIds === []) {
      return [];
    }

    $ids = array_map(static fn (JobApplicationId $id): string => $id->value(), $applicationIds);
    $rows = $this->db->fetchAll(
      'SELECT application_id, author_id, body, created_at FROM application_notes'
      . ' WHERE application_id IN (' . self::placeholders($ids) . ') ORDER BY id ASC',
      $ids
    );

    $notes = [];
    foreach ($rows as $row) {
      $notes[(string) $row['application_id']][] = ApplicationNote::reconstitute(
        JobApplicationId::fromString((string) $row['application_id']),
        self::optionalUserId($row['author_id'] ?? null),
        (string) $row['body'],
        Sql::toDate($row['created_at'] ?? null)
      );
    }

    return $notes;
  }

  /**
   * @param array<int,array<string,mixed>> $rows
   * @return list<JobApplication>
   */
  private function hydrate(array $rows): array
  {
    if ($rows === []) {
      return [];
    }

    $history = $this->loadHistory(array_map(static fn (array $row): string => (string) $row['id'], $rows));

    return array_map(
      static fn (array $row): JobApplication => JobApplication::reconstitute(
        JobApplicationId::fromString((string) $row['id']),
        VacancyId::fromString((string) $row['vacancy_id']),
        UserId::fromString((string) $row['user_id']),
        Sql::toDate($row['created_at'] ?? null),
        ApplicationStatus::fromStored(isset($row['status']) ? (string) $row['status'] : null),
        Sql::toDate($row['status_changed_at'] ?? $row['created_at'] ?? null),
        isset($row['candidate_feedback']) && $row['candidate_feedback'] !== '' ? (string) $row['candidate_feedback'] : null,
        $history[(string) $row['id']] ?? []
      ),
      array_values($rows)
    );
  }

  /**
   * @param list<string> $applicationIds
   * @return array<string,list<ApplicationEvent>>
   */
  private function loadHistory(array $applicationIds): array
  {
    $rows = $this->db->fetchAll(
      'SELECT application_id, from_status, to_status, actor_id, reason, created_at FROM application_events'
      . ' WHERE application_id IN (' . self::placeholders($applicationIds) . ') ORDER BY id ASC',
      $applicationIds
    );

    $history = [];
    foreach ($rows as $row) {
      $history[(string) $row['application_id']][] = new ApplicationEvent(
        isset($row['from_status']) ? ApplicationStatus::fromStored((string) $row['from_status']) : null,
        ApplicationStatus::fromStored((string) $row['to_status']),
        self::optionalUserId($row['actor_id'] ?? null),
        isset($row['reason']) && $row['reason'] !== '' ? (string) $row['reason'] : null,
        Sql::toDate($row['created_at'] ?? null)
      );
    }

    return $history;
  }

  private static function optionalUserId(mixed $value): ?UserId
  {
    return is_string($value) && UserId::isValid($value) ? UserId::fromString($value) : null;
  }

  /**
   * @param list<string> $values
   */
  private static function placeholders(array $values): string
  {
    return implode(', ', array_fill(0, count($values), '?'));
  }
}
