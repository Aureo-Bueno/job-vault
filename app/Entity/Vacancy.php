<?php

namespace App\Entity;

use \App\Db\Database;
use \App\Util\Logger;
use \PDO;

/**
 * Vacancy Entity Class
 *
 * Represents a job vacancy with CRUD operations.
 * Handles vacancy creation, updates, deletion, and queries.
 *
 * @package App\Entity
 * @version 2.0
 */
class Vacancy
{
  /**
   * Unique vacancy identifier
   *
   * @var int|null
   */
  public $id;

  /**
   * Job title
   *
   * @var string
   */
  public $title;

  /**
   * Job description
   *
   * @var string
   */
  public $description;

  /**
   * Vacancy status (s=active, n=inactive)
   *
   * @var string
   */
  public $is_active;

  /**
   * Publication date and time
   *
   * @var string
   */
  public $created_at;

  /**
   * Application logger
   *
   * @var Logger
   */
  private static $logger;

  /**
   * Initialize logger
   *
   * @return void
   */
  private static function initLogger()
  {
    if (!isset(self::$logger)) {
      self::$logger = new Logger('vacancy');
    }
  }

  /**
   * Register a new vacancy
   *
   * Inserts vacancy into database with current timestamp.
   * Logs the creation event.
   *
   * Example:
   *   $vacancy = new Vacancy();
   *   $vacancy->title = 'PHP Developer';
   *   $vacancy->description = 'Senior PHP developer...';
   *   $vacancy->is_active = 's';
   *   $vacancy->create();
   *
   * @return bool Always returns true on success
   * @throws PDOException If insert fails
   */
  public function create()
  {
    self::initLogger();

    try {
      $this->created_at = date('Y-m-d H:i:s');

      $obDatabase = new Database('vacancies');
      $this->id = $obDatabase->insert([
        'title' => $this->title,
        'description' => $this->description,
        'is_active' => $this->is_active,
        'created_at' => $this->created_at
      ]);

      self::$logger->info('New vacancy created', [
        'vacancy_id' => $this->id,
        'title' => $this->title,
        'status' => $this->is_active
      ]);

      return true;
    } catch (\PDOException $e) {
      self::$logger->error('Failed to create vacancy', [
        'error' => $e->getMessage(),
        'title' => $this->title
      ]);
      throw $e;
    }
  }

  /**
   * Update vacancy information
   *
   * Updates existing vacancy by ID.
   * Logs the update event.
   *
   * Example:
   *   $vacancy = Vacancy::getVacancy(1);
   *   $vacancy->title = 'Updated Title';
   *   $vacancy->update();
   *
   * @return bool True on success
   * @throws PDOException If update fails
   */
  public function update()
  {
    self::initLogger();

    try {
      $result = (new Database('vacancies'))->update("id = '{$this->id}'", [
        'title' => $this->title,
        'description' => $this->description,
        'is_active' => $this->is_active,
        'created_at' => $this->created_at
      ]);

      self::$logger->info('Vacancy updated', [
        'vacancy_id' => $this->id,
        'title' => $this->title
      ]);

      return $result;
    } catch (\PDOException $e) {
      self::$logger->error('Failed to update vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $this->id
      ]);
      throw $e;
    }
  }

  /**
   * Delete a vacancy from database
   *
   * Removes vacancy by ID.
   * Logs the deletion event.
   *
   * @return bool True on success
   * @throws PDOException If delete fails
   */
  public function delete()
  {
    self::initLogger();

    try {
      $result = (new Database('vacancies'))->delete("id = '{$this->id}'");

      self::$logger->info('Vacancy deleted', [
        'vacancy_id' => $this->id,
        'title' => $this->title
      ]);

      return $result;
    } catch (\PDOException $e) {
      self::$logger->error('Failed to delete vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $this->id
      ]);
      throw $e;
    }
  }

  /**
   * Get vacancies with optional filters and pagination
   *
   * Fetches multiple vacancies with WHERE, ORDER BY, and LIMIT clauses.
   * Returns array of Vacancy objects.
   *
   * Example:
   *   $vacancies = Vacancy::getVacancies('is_active = "s"', 'created_at DESC', '0,10');
   *   foreach ($vacancies as $vacancy) {
   *       echo $vacancy->title;
   *   }
   *
   * @param string|null $where WHERE clause without "WHERE" keyword
   * @param string|null $order ORDER BY clause without "ORDER BY" keyword
   * @param string|null $limit LIMIT clause. Format: "10" or "0,10"
   * @return array Array of Vacancy objects
   */
  public static function getVacancies($where = null, $order = null, $limit = null)
  {
    try {
      return (new Database('vacancies'))->select($where, $order, $limit)
        ->fetchAll(PDO::FETCH_CLASS, self::class);
    } catch (\PDOException $e) {
      self::initLogger();
      self::$logger->error('Failed to fetch vacancies', [
        'error' => $e->getMessage(),
        'where' => $where
      ]);
      return [];
    }
  }

  /**
   * Get total count of vacancies matching criteria
   *
   * Counts vacancies with optional WHERE clause.
   *
   * Example:
   *   $total = Vacancy::getVacancyCount('is_active = "s"');
   *   echo "Total active vacancies: " . $total;
   *
   * @param string|null $where WHERE clause without "WHERE" keyword
   * @return int Number of matching vacancies
   */
  public static function getVacancyCount($where = null)
  {
    try {
      $result = (new Database('vacancies'))->execute(
        'SELECT COUNT(*) as qtd FROM vacancies ' .
          (!is_null($where) && strlen($where) ? 'WHERE ' . $where : '')
      );
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return (int) ($row['qtd'] ?? 0);
    } catch (\PDOException $e) {
      self::initLogger();
      self::$logger->error('Failed to count vacancies', [
        'error' => $e->getMessage()
      ]);
      return 0;
    }
  }

  /**
   * Get single vacancy by ID
   *
   * Fetches a specific vacancy by its ID.
   * Returns null if vacancy not found.
   *
   * Example:
   *   $vacancy = Vacancy::getVacancy(1);
   *   if ($vacancy) {
   *       echo $vacancy->title;
   *   }
   *
   * @param int $id Vacancy ID
   * @return Vacancy|null Vacancy object or null if not found
   */
  public static function getVacancy($id)
  {
    try {
      return (new Database('vacancies'))->select("id = '{$id}'")
        ->fetchObject(self::class) ?: null;
    } catch (\PDOException $e) {
      self::initLogger();
      self::$logger->error('Failed to fetch vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $id
      ]);
      return null;
    }
  }
}
