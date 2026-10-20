<?php

namespace App\Infrastructure\Persistence;

use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationId;
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

  public function forVacancy(VacancyId $vacancyId): array
  {
    $rows = $this->db->fetchAll(
      'SELECT id, vacancy_id, user_id, created_at FROM applications WHERE vacancy_id = ? ORDER BY created_at DESC, id ASC',
      [$vacancyId->value()]
    );

    return array_map(
      static fn (array $row): JobApplication => JobApplication::reconstitute(
        JobApplicationId::fromString((string) $row['id']),
        VacancyId::fromString((string) $row['vacancy_id']),
        UserId::fromString((string) $row['user_id']),
        Sql::toDate($row['created_at'] ?? null)
      ),
      $rows
    );
  }

  public function countPerVacancy(): array
  {
    $rows = $this->db->fetchAll('SELECT vacancy_id, COUNT(*) AS total FROM applications GROUP BY vacancy_id');
    $counts = [];
    foreach ($rows as $row) {
      $counts[(string) $row['vacancy_id']] = (int) $row['total'];
    }

    return $counts;
  }
}
