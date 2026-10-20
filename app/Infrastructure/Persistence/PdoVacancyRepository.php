<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Company\CompanyId;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\Benefit;
use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\EmploymentTerms;
use App\Domain\Vacancy\SalaryRange;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyDeadline;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyLocation;
use App\Domain\Vacancy\VacancyPositions;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use App\Infrastructure\Persistence\Search\FullTextTokenizer;
use App\Infrastructure\Persistence\Search\VacancySearchDocument;
use DateTimeImmutable;

final class PdoVacancyRepository implements VacancyRepositoryInterface
{
  private const COLUMNS = 'id, company_id, title, description, area, seniority, work_mode, status, close_reason, closed_at, positions,'
    . ' contract_type, salary_min, salary_max, city, state, closes_at, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(VacancyId $id): ?Vacancy
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM vacancies WHERE id = ?', [$id->value()]);
    if ($row === null) {
      return null;
    }

    return $this->hydrate([$row])[0];
  }

  public function matching(VacancyFilter $filter, PageRequest $page): array
  {
    [$where, $params] = $this->criteria($filter);
    $fullText = $filter->text !== null ? FullTextTokenizer::booleanQuery($filter->text) : null;

    if ($fullText !== null) {
      $params['ft_rank'] = $fullText;
      $sql = 'SELECT ' . self::COLUMNS . ', MATCH(search_document) AGAINST(:ft_rank IN BOOLEAN MODE) AS relevance'
        . ' FROM vacancies' . $where . ' ORDER BY relevance DESC, created_at DESC, id ASC';
    } else {
      $sql = 'SELECT ' . self::COLUMNS . ' FROM vacancies' . $where . ' ORDER BY created_at DESC, id ASC';
    }

    $rows = $this->db->fetchAll($sql . Sql::limit($page), $params);

    return $this->hydrate($rows);
  }

  public function countMatching(VacancyFilter $filter): int
  {
    [$where, $params] = $this->criteria($filter);
    return (int) $this->db->fetchValue('SELECT COUNT(*) FROM vacancies' . $where, $params);
  }

  public function openPastDeadline(DateTimeImmutable $now): array
  {
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM vacancies WHERE status = ? AND closes_at < ? ORDER BY closes_at ASC, id ASC',
      [VacancyStatus::Open->value, VacancyDeadline::today($now)]
    );

    return $this->hydrate($rows);
  }

  public function save(Vacancy $vacancy): void
  {
    $vid = $vacancy->id()->value();
    $terms = $vacancy->terms();
    $values = [
      $vacancy->companyId()?->value(),
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->area()->value,
      $vacancy->seniority()->value,
      $vacancy->workMode()->value,
      $vacancy->status()->value,
      $vacancy->closeReason()?->value,
      $vacancy->closedAt() !== null ? Sql::fromDate($vacancy->closedAt()) : null,
      $vacancy->positions()->value(),
      $terms->contractType()->value,
      $terms->salary()->min(),
      $terms->salary()->max(),
      $terms->location()?->city(),
      $terms->location()?->state()->value,
      $vacancy->closesAt()?->value(),
      VacancySearchDocument::forVacancy($vacancy),
      Sql::fromDate($vacancy->publishedAt()),
      $vid,
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM vacancies WHERE id = ?', [$vid]);
    $this->db->run(
      $exists
        ? 'UPDATE vacancies SET company_id = ?, title = ?, description = ?, area = ?, seniority = ?, work_mode = ?,'
          . ' status = ?, close_reason = ?, closed_at = ?, positions = ?,'
          . ' contract_type = ?, salary_min = ?, salary_max = ?, city = ?, state = ?, closes_at = ?, search_document = ?, created_at = ? WHERE id = ?'
        : 'INSERT INTO vacancies (company_id, title, description, area, seniority, work_mode,'
          . ' status, close_reason, closed_at, positions, contract_type, salary_min, salary_max, city, state, closes_at, search_document,'
          . ' created_at, id) VALUES (' . implode(', ', array_fill(0, 19, '?')) . ')',
      $values
    );

    $this->db->run('DELETE FROM vacancy_topics WHERE vacancy_id = ?', [$vid]);
    foreach ($vacancy->topics() as $topic) {
      $this->db->run(
        'INSERT INTO vacancy_topics (vacancy_id, slug, label) VALUES (?, ?, ?)',
        [$vid, $topic->slug(), $topic->label()]
      );
    }

    $this->db->run('DELETE FROM vacancy_benefits WHERE vacancy_id = ?', [$vid]);
    foreach ($terms->benefits() as $benefit) {
      $this->db->run(
        'INSERT INTO vacancy_benefits (vacancy_id, slug, label) VALUES (?, ?, ?)',
        [$vid, $benefit->slug(), $benefit->label()]
      );
    }
  }

  public function remove(VacancyId $id): void
  {
    $this->db->run('DELETE FROM vacancies WHERE id = ?', [$id->value()]);
  }

  /**
   * @return array{0:string,1:array<string,string|int>}
   */
  private function criteria(VacancyFilter $filter): array
  {
    $conditions = [];
    $params = [];

    // Only stopwords/punctuation typed: nothing to search, the other filters still apply.
    $fullText = $filter->text !== null ? FullTextTokenizer::booleanQuery($filter->text) : null;
    if ($fullText !== null) {
      $conditions[] = 'MATCH(search_document) AGAINST(:ft IN BOOLEAN MODE)';
      $params['ft'] = $fullText;
    }

    if ($filter->status !== null) {
      $conditions[] = 'status = :status';
      $params['status'] = $filter->status->value;
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

    if ($filter->minSalary !== null) {
      // Same rule as SalaryRange::reaches(): the top of the range; "A combinar" (both NULL) never matches.
      $conditions[] = 'COALESCE(salary_max, salary_min) >= :min_salary';
      $params['min_salary'] = $filter->minSalary;
    }

    if ($filter->state !== null) {
      $conditions[] = 'state = :state';
      $params['state'] = $filter->state->value;
    }

    if ($filter->contractType !== null) {
      $conditions[] = 'contract_type = :contract_type';
      $params['contract_type'] = $filter->contractType->value;
    }

    if ($filter->acceptingApplicationsAt !== null) {
      $conditions[] = '(closes_at IS NULL OR closes_at >= :today)';
      $params['today'] = VacancyDeadline::today($filter->acceptingApplicationsAt);
    }

    return [Sql::where($conditions), $params];
  }

  /**
   * @param list<array<string,mixed>> $rows
   * @return list<Vacancy>
   */
  private function hydrate(array $rows): array
  {
    if ($rows === []) {
      return [];
    }

    $ids = array_map(static fn (array $r): string => (string) $r['id'], $rows);
    $topics = $this->loadTags('vacancy_topics', $ids, TopicTag::reconstitute(...));
    $benefits = $this->loadTags('vacancy_benefits', $ids, Benefit::reconstitute(...));

    return array_map(
      fn (array $row): Vacancy => $this->map($row, $topics[(string) $row['id']] ?? [], $benefits[(string) $row['id']] ?? []),
      $rows
    );
  }

  /**
   * Loads (slug, label) rows of a tag table grouped by vacancy.
   *
   * @template T
   * @param 'vacancy_topics'|'vacancy_benefits' $table
   * @param list<string> $vacancyIds
   * @param callable(string,string):T $factory
   * @return array<string,list<T>>
   */
  private function loadTags(string $table, array $vacancyIds, callable $factory): array
  {
    $placeholders = implode(', ', array_fill(0, count($vacancyIds), '?'));
    $rows = $this->db->fetchAll(
      'SELECT vacancy_id, slug, label FROM ' . $table . ' WHERE vacancy_id IN (' . $placeholders . ') ORDER BY label ASC',
      $vacancyIds
    );

    $grouped = [];
    foreach ($rows as $row) {
      $grouped[(string) $row['vacancy_id']][] = $factory((string) $row['slug'], (string) $row['label']);
    }

    return $grouped;
  }

  /**
   * @param array<string,mixed> $row
   * @param list<TopicTag> $topics
   * @param list<Benefit> $benefits
   */
  private function map(array $row, array $topics, array $benefits): Vacancy
  {
    $str = static fn (string $key): ?string => isset($row[$key]) ? (string) $row[$key] : null;

    return Vacancy::reconstitute(
      VacancyId::fromString((string) $row['id']),
      VacancyTitle::fromString((string) $row['title']),
      VacancyDescription::fromString((string) $row['description']),
      VacancyStatus::fromStored($str('status')),
      Sql::toDate($row['created_at'] ?? null),
      isset($row['company_id']) && $row['company_id'] !== '' ? CompanyId::fromString((string) $row['company_id']) : null,
      WorkArea::fromStored($str('area')),
      Seniority::fromStored($str('seniority')),
      WorkMode::fromStored($str('work_mode')),
      $topics,
      VacancyPositions::fromStored($row['positions'] ?? 1),
      VacancyCloseReason::fromStored($str('close_reason')),
      isset($row['closed_at']) && $row['closed_at'] !== '' ? Sql::toDate($row['closed_at']) : null,
      EmploymentTerms::reconstitute(
        ContractType::fromStored($str('contract_type')),
        SalaryRange::fromStored($row['salary_min'] ?? null, $row['salary_max'] ?? null),
        VacancyLocation::fromStored($str('city'), $str('state')),
        $benefits
      ),
      VacancyDeadline::fromStored($row['closes_at'] ?? null)
    );
  }
}
