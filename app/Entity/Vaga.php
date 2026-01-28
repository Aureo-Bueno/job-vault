<?php

namespace App\Entity;

use \App\Db\Database;
use \App\Util\Logger;
use \PDO;

/**
 * Vaga (Job Vacancy) Entity Class
 *
 * Represents a job vacancy with CRUD operations.
 * Handles vacancy creation, updates, deletion, and queries.
 *
 * @package App\Entity
 * @version 2.0
 */
class Vaga
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
  public $titulo;

  /**
   * Job description
   *
   * @var string
   */
  public $descricao;

  /**
   * Vacancy status (s=active, n=inactive)
   *
   * @var string
   */
  public $ativo;

  /**
   * Publication date and time
   *
   * @var string
   */
  public $data;

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
      self::$logger = new Logger('vaga');
    }
  }

  /**
   * Register a new vacancy
   *
   * Inserts vacancy into database with current timestamp.
   * Logs the creation event.
   *
   * Example:
   *   $vaga = new Vaga();
   *   $vaga->titulo = 'PHP Developer';
   *   $vaga->descricao = 'Senior PHP developer...';
   *   $vaga->ativo = 's';
   *   $vaga->cadastrar();
   *
   * @return bool Always returns true on success
   * @throws PDOException If insert fails
   */
  public function cadastrar()
  {
    self::initLogger();

    try {
      $this->data = date('Y-m-d H:i:s');

      $obDatabase = new Database('vagas');
      $this->id = $obDatabase->insert([
        'titulo' => $this->titulo,
        'descricao' => $this->descricao,
        'ativo' => $this->ativo,
        'data' => $this->data
      ]);

      self::$logger->info('New vacancy created', [
        'vacancy_id' => $this->id,
        'title' => $this->titulo,
        'status' => $this->ativo
      ]);

      return true;
    } catch (\PDOException $e) {
      self::$logger->error('Failed to create vacancy', [
        'error' => $e->getMessage(),
        'title' => $this->titulo
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
   *   $vaga = Vaga::getVaga(1);
   *   $vaga->titulo = 'Updated Title';
   *   $vaga->atualizar();
   *
   * @return bool True on success
   * @throws PDOException If update fails
   */
  public function atualizar()
  {
    self::initLogger();

    try {
      $result = (new Database('vagas'))->update('id = ' . intval($this->id), [
        'titulo' => $this->titulo,
        'descricao' => $this->descricao,
        'ativo' => $this->ativo,
        'data' => $this->data
      ]);

      self::$logger->info('Vacancy updated', [
        'vacancy_id' => $this->id,
        'title' => $this->titulo
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
  public function exluir()
  {
    self::initLogger();

    try {
      $result = (new Database('vagas'))->delete('id = ' . intval($this->id));

      self::$logger->info('Vacancy deleted', [
        'vacancy_id' => $this->id,
        'title' => $this->titulo
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
   * Returns array of Vaga objects.
   *
   * Example:
   *   $vagas = Vaga::getVagas('ativo = "s"', 'data DESC', '0,10');
   *   foreach ($vagas as $vaga) {
   *       echo $vaga->titulo;
   *   }
   *
   * @param string|null $where WHERE clause without "WHERE" keyword
   * @param string|null $order ORDER BY clause without "ORDER BY" keyword
   * @param string|null $limit LIMIT clause. Format: "10" or "0,10"
   * @return array Array of Vaga objects
   */
  public static function getVagas($where = null, $order = null, $limit = null)
  {
    try {
      return (new Database('vagas'))->select($where, $order, $limit)
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
   *   $total = Vaga::getQuantidadeVagas('ativo = "s"');
   *   echo "Total active vacancies: " . $total;
   *
   * @param string|null $where WHERE clause without "WHERE" keyword
   * @return int Number of matching vacancies
   */
  public static function getQuantidadeVagas($where = null)
  {
    try {
      $result = (new Database('vagas'))->execute(
        'SELECT COUNT(*) as qtd FROM vagas ' .
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
   *   $vaga = Vaga::getVaga(1);
   *   if ($vaga) {
   *       echo $vaga->titulo;
   *   }
   *
   * @param int $id Vacancy ID
   * @return Vaga|null Vaga object or null if not found
   */
  public static function getVaga($id)
  {
    try {
      return (new Database('vagas'))->select('id = ' . intval($id))
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
