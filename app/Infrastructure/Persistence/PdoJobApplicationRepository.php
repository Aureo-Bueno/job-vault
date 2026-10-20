<?php

namespace App\Infrastructure\Persistence;

use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

final class PdoJobApplicationRepository implements JobApplicationRepositoryInterface
{
  public function __construct(private Database $db)
  {
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
    $this->db->run(
      'INSERT INTO applications (id, user_id, vacancy_id, created_at) VALUES (?, ?, ?, ?)',
      [
        $application->id()->value(),
        $application->candidateId()->value(),
        $application->vacancyId()->value(),
        Sql::fromDate($application->submittedAt()),
      ]
    );
  }

  public function vacancyIdsAppliedBy(UserId $candidateId): array
  {
    $rows = $this->db->fetchAll('SELECT vacancy_id FROM applications WHERE user_id = ?', [$candidateId->value()]);
    return array_map(static fn (array $row): string => (string) $row['vacancy_id'], $rows);
  }
}
