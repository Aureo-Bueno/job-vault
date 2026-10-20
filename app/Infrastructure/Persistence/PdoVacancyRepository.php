<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Company\CompanyId;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
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
  private const COLUMNS = 'id, company_id, title, description, area, seniority, work_mode, is_active, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(VacancyId $id): ?Vacancy
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM vacancies WHERE id = ?', [$id->value()]);
    if ($row === null) {
      return null;
    }

    $topics = $this->loadTopics([$id->value()]);
    return $this->map($row, $topics[$id->value()] ?? []);
  }

  public function matching(VacancyFilter $filter, PageRequest $page): array
  {
    [$where, $params] = $this->criteria($filter);
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM vacancies' . $where . ' ORDER BY created_at DESC, id ASC' . Sql::limit($page),
      $params
    );

    if ($rows === []) {
      return [];
    }

    $ids = array_map(static fn (array $r): string => (string) $r['id'], $rows);
    $topicsByVacancy = $this->loadTopics($ids);

    $result = [];
    foreach ($rows as $row) {
      $vid = (string) $row['id'];
      $result[] = $this->map($row, $topicsByVacancy[$vid] ?? []);
    }

    return $result;
  }

  public function countMatching(VacancyFilter $filter): int
  {
    [$where, $params] = $this->criteria($filter);
    return (int) $this->db->fetchValue('SELECT COUNT(*) FROM vacancies' . $where, $params);
  }

  public function save(Vacancy $vacancy): void
  {
    $vid = $vacancy->id()->value();
    $values = [
      $vacancy->companyId()?->value(),
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->area()->value,
      $vacancy->seniority()->value,
      $vacancy->workMode()->value,
      $vacancy->isOpen() ? self::ACTIVE : self::INACTIVE,
      Sql::fromDate($vacancy->publishedAt()),
      $vid,
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM vacancies WHERE id = ?', [$vid]);
    $this->db->run(
      $exists
        ? 'UPDATE vacancies SET company_id = ?, title = ?, description = ?, area = ?, seniority = ?, work_mode = ?, is_active = ?, created_at = ? WHERE id = ?'
        : 'INSERT INTO vacancies (company_id, title, description, area, seniority, work_mode, is_active, created_at, id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
      $values
    );

    $this->db->run('DELETE FROM vacancy_topics WHERE vacancy_id = ?', [$vid]);
    foreach ($vacancy->topics() as $topic) {
      $this->db->run(
        'INSERT INTO vacancy_topics (vacancy_id, slug, label) VALUES (?, ?, ?)',
        [$vid, $topic->slug(), $topic->label()]
      );
    }
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
      $conditions[] = '(title LIKE :title OR EXISTS (SELECT 1 FROM vacancy_topics vt WHERE vt.vacancy_id = vacancies.id AND vt.label LIKE :topic_label))';
      $params['title'] = Sql::likePattern($filter->title);
      $params['topic_label'] = $params['title'];
    }

    if ($filter->status !== null) {
      $conditions[] = 'is_active = :status';
      $params['status'] = $filter->status->isActive() ? self::ACTIVE : self::INACTIVE;
    }

    if ($filter->companyId !== null) {
      $conditions[] = 'company_id = :company_id';
      $params['company_id'] = $filter->companyId->value();
    }

    if ($filter->area !== null) {
      $conditions[] = 'area = :area';
      $params['area'] = $filter->area->value;
    }

    if ($filter->workMode !== null) {
      $conditions[] = 'work_mode = :work_mode';
      $params['work_mode'] = $filter->workMode->value;
    }

    return [Sql::where($conditions), $params];
  }

  /**
   * @param list<string> $vacancyIds
   * @return array<string,list<TopicTag>>
   */
  private function loadTopics(array $vacancyIds): array
  {
    if ($vacancyIds === []) {
      return [];
    }

    $placeholders = implode(', ', array_fill(0, count($vacancyIds), '?'));
    $rows = $this->db->fetchAll(
      'SELECT vacancy_id, slug, label FROM vacancy_topics WHERE vacancy_id IN (' . $placeholders . ') ORDER BY label ASC',
      $vacancyIds
    );

    $grouped = [];
    foreach ($rows as $row) {
      $vid = (string) $row['vacancy_id'];
      $grouped[$vid][] = TopicTag::reconstitute((string) $row['slug'], (string) $row['label']);
    }

    return $grouped;
  }

  /**
   * @param array<string,mixed> $row
   * @param list<TopicTag> $topics
   */
  private function map(array $row, array $topics): Vacancy
  {
    return Vacancy::reconstitute(
      VacancyId::fromString((string) $row['id']),
      VacancyTitle::fromString((string) $row['title']),
      VacancyDescription::fromString((string) $row['description']),
      VacancyStatus::fromBool(($row['is_active'] ?? self::INACTIVE) === self::ACTIVE),
      Sql::toDate($row['created_at'] ?? null),
      isset($row['company_id']) && $row['company_id'] !== '' ? CompanyId::fromString((string) $row['company_id']) : null,
      WorkArea::fromStored(isset($row['area']) ? (string) $row['area'] : null),
      Seniority::fromStored(isset($row['seniority']) ? (string) $row['seniority'] : null),
      WorkMode::fromStored(isset($row['work_mode']) ? (string) $row['work_mode'] : null),
      $topics
    );
  }
}
