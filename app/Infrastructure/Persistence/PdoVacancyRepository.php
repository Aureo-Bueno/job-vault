<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Vacancy;
use App\Domain\Repository\VacancyRepositoryInterface;
use App\Util\Logger;
use App\Util\Uuid;
use PDO;

class PdoVacancyRepository implements VacancyRepositoryInterface
{
  private Database $database;
  private Logger $logger;

  public function __construct()
  {
    $this->database = new Database('vacancies');
    $this->logger = new Logger('vacancy');
  }

  /** @return Vacancy[] */
  public function findAll(
    ?string $where = null,
    ?string $order = null,
    ?string $limit = null,
    array $params = []
  ): array
  {
    try {
      $statement = $this->database->select($where, $order, $limit, '*', $params);
      $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
      return array_map([$this, 'mapRow'], $rows);
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch vacancies', [
        'error' => $e->getMessage(),
        'where' => $where
      ]);
      throw $e;
    }
  }

  public function findById(string $id): ?Vacancy
  {
    try {
      $statement = $this->database->execute('SELECT * FROM vacancies WHERE id = ?', [$id]);
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      return $row ? $this->mapRow($row) : null;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $id
      ]);
      throw $e;
    }
  }

  public function count(?string $where = null, array $params = []): int
  {
    try {
      return $this->database->count($where, $params);
    } catch (\Throwable $e) {
      $this->logger->error('Failed to count vacancies', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  public function create(Vacancy $vacancy): Vacancy
  {
    try {
      if ($vacancy->id === null) {
        $vacancy->id = Uuid::generateV4();
      }

      $this->database->insert([
        'id' => $vacancy->id,
        'title' => $vacancy->title,
        'description' => $vacancy->description,
        'is_active' => $vacancy->isActive,
        'created_at' => $vacancy->createdAt
      ]);

      $this->logger->info('New vacancy created', [
        'vacancy_id' => $vacancy->id,
        'title' => $vacancy->title,
        'status' => $vacancy->isActive
      ]);
    } catch (\Throwable $e) {
      $this->logger->error('Failed to create vacancy', [
        'error' => $e->getMessage(),
        'title' => $vacancy->title
      ]);
      throw $e;
    }

    return $vacancy;
  }

  public function update(Vacancy $vacancy): bool
  {
    try {
      $this->database->execute(
        'UPDATE vacancies SET title = ?, description = ?, is_active = ?, created_at = ? WHERE id = ?',
        [$vacancy->title, $vacancy->description, $vacancy->isActive, $vacancy->createdAt, $vacancy->id]
      );

      $this->logger->info('Vacancy updated', [
        'vacancy_id' => $vacancy->id,
        'title' => $vacancy->title
      ]);

      return true;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to update vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $vacancy->id
      ]);
      throw $e;
    }
  }

  public function delete(string $id): bool
  {
    try {
      $this->database->execute('DELETE FROM vacancies WHERE id = ?', [$id]);
      $this->logger->info('Vacancy deleted', [
        'vacancy_id' => $id
      ]);
      return true;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to delete vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $id
      ]);
      throw $e;
    }
  }

  private function mapRow(array $row): Vacancy
  {
    return new Vacancy(
      isset($row['id']) ? (string) $row['id'] : null,
      $row['title'] ?? '',
      $row['description'] ?? '',
      $row['is_active'] ?? 'n',
      $row['created_at'] ?? ''
    );
  }
}
