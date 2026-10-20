<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\PageRequest;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;

final class PdoVacancyRepository implements VacancyRepositoryInterface
{
  /** Legacy column values of `vacancies.is_active` */
  private const ACTIVE = 's';
  private const INACTIVE = 'n';
  private const COLUMNS = 'id, title, description, is_active, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(VacancyId $id): ?Vacancy
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM vacancies WHERE id = ?', [$id->value()]);
    return $row ? $this->map($row) : null;
  }

  public function matching(VacancyFilter $filter, PageRequest $page): array
  {
    [$where, $params] = $this->criteria($filter);
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM vacancies' . $where . ' ORDER BY created_at DESC, id ASC' . Sql::limit($page),
      $params
    );

    return array_map([$this, 'map'], $rows);
  }

  public function countMatching(VacancyFilter $filter): int
  {
    [$where, $params] = $this->criteria($filter);
    return (int) $this->db->fetchValue('SELECT COUNT(*) FROM vacancies' . $where, $params);
  }

  public function save(Vacancy $vacancy): void
  {
    $values = [
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->isOpen() ? self::ACTIVE : self::INACTIVE,
      Sql::fromDate($vacancy->publishedAt()),
      $vacancy->id()->value(),
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM vacancies WHERE id = ?', [$vacancy->id()->value()]);
    $this->db->run(
      $exists
        ? 'UPDATE vacancies SET title = ?, description = ?, is_active = ?, created_at = ? WHERE id = ?'
        : 'INSERT INTO vacancies (title, description, is_active, created_at, id) VALUES (?, ?, ?, ?, ?)',
      $values
    );
  }

  public function remove(VacancyId $id): void
  {
    $this->db->run('DELETE FROM vacancies WHERE id = ?', [$id->value()]);
  }

  /**
   * @return array{0:string,1:array<string,string>}
   */
  private function criteria(VacancyFilter $filter): array
  {
    $conditions = [];
    $params = [];

    if ($filter->title !== null) {
      $conditions[] = 'title LIKE :title';
      $params['title'] = Sql::likePattern($filter->title);
    }

    if ($filter->status !== null) {
      $conditions[] = 'is_active = :status';
      $params['status'] = $filter->status->isActive() ? self::ACTIVE : self::INACTIVE;
    }

    return [Sql::where($conditions), $params];
  }

  /**
   * @param array<string,mixed> $row
   */
  private function map(array $row): Vacancy
  {
    return Vacancy::reconstitute(
      VacancyId::fromString((string) $row['id']),
      VacancyTitle::fromString((string) $row['title']),
      VacancyDescription::fromString((string) $row['description']),
      VacancyStatus::fromBool(($row['is_active'] ?? self::INACTIVE) === self::ACTIVE),
      Sql::toDate($row['created_at'] ?? null)
    );
  }
}
