<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Vaga;
use App\Domain\Repository\VagaRepositoryInterface;
use App\Util\Logger;
use PDO;

class PdoVagaRepository implements VagaRepositoryInterface
{
  private Database $db;
  private Logger $logger;

  public function __construct()
  {
    $this->db = new Database('vagas');
    $this->logger = new Logger('vaga');
  }

  /** @return Vaga[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    try {
      $rows = $this->db->select($where, $order, $limit)->fetchAll(PDO::FETCH_ASSOC);
      return array_map([$this, 'mapRow'], $rows);
    } catch (\PDOException $e) {
      $this->logger->error('Failed to fetch vacancies', [
        'error' => $e->getMessage(),
        'where' => $where
      ]);
      return [];
    }
  }

  public function findById(int $id): ?Vaga
  {
    try {
      $row = $this->db->select('id = ' . intval($id))->fetch(PDO::FETCH_ASSOC);
      return $row ? $this->mapRow($row) : null;
    } catch (\PDOException $e) {
      $this->logger->error('Failed to fetch vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $id
      ]);
      return null;
    }
  }

  public function count(?string $where = null): int
  {
    try {
      return $this->db->count($where);
    } catch (\PDOException $e) {
      $this->logger->error('Failed to count vacancies', [
        'error' => $e->getMessage()
      ]);
      return 0;
    }
  }

  public function create(Vaga $vaga): Vaga
  {
    try {
      $vaga->id = (int) $this->db->insert([
        'titulo' => $vaga->titulo,
        'descricao' => $vaga->descricao,
        'ativo' => $vaga->ativo,
        'data' => $vaga->data
      ]);

      $this->logger->info('New vacancy created', [
        'vacancy_id' => $vaga->id,
        'title' => $vaga->titulo,
        'status' => $vaga->ativo
      ]);
    } catch (\PDOException $e) {
      $this->logger->error('Failed to create vacancy', [
        'error' => $e->getMessage(),
        'title' => $vaga->titulo
      ]);
      throw $e;
    }

    return $vaga;
  }

  public function update(Vaga $vaga): bool
  {
    try {
      $this->db->update('id = ' . intval($vaga->id), [
        'titulo' => $vaga->titulo,
        'descricao' => $vaga->descricao,
        'ativo' => $vaga->ativo,
        'data' => $vaga->data
      ]);

      $this->logger->info('Vacancy updated', [
        'vacancy_id' => $vaga->id,
        'title' => $vaga->titulo
      ]);

      return true;
    } catch (\PDOException $e) {
      $this->logger->error('Failed to update vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $vaga->id
      ]);
      throw $e;
    }
  }

  public function delete(int $id): bool
  {
    try {
      $this->db->delete('id = ' . intval($id));
      $this->logger->info('Vacancy deleted', [
        'vacancy_id' => $id
      ]);
      return true;
    } catch (\PDOException $e) {
      $this->logger->error('Failed to delete vacancy', [
        'error' => $e->getMessage(),
        'vacancy_id' => $id
      ]);
      throw $e;
    }
  }

  private function mapRow(array $row): Vaga
  {
    return new Vaga(
      isset($row['id']) ? (int) $row['id'] : null,
      $row['titulo'] ?? '',
      $row['descricao'] ?? '',
      $row['ativo'] ?? 'n',
      $row['data'] ?? ''
    );
  }
}
