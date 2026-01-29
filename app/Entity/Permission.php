<?php

namespace App\Entity;

use App\Db\Database;

/**
 * Permission Entity
 *
 * Represents a permission in the system
 *
 * @package App\Entity
 * @version 1.0
 */
class Permission
{
  public $id;
  public $nome;
  public $descricao;
  public $modulo;
  public $acao;
  public $created_at;

  private $db;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->db = new Database('permissions');
  }

  /**
   * Get permission by ID
   *
   * @param int $id Permission ID
   * @return Permission|null Permission object or null if not found
   */
  public static function getPermissionById($id)
  {
    $db = new Database('permissions');
    $result = $db->select("id = {$id}");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $permission = new self();
    $permData = $result[0];
    $permission->id = $permData->id;
    $permission->nome = $permData->nome;
    $permission->descricao = $permData->descricao;
    $permission->modulo = $permData->modulo;
    $permission->acao = $permData->acao;
    $permission->created_at = $permData->created_at;

    return $permission;
  }

  /**
   * Get permission by name
   *
   * @param string $nome Permission name
   * @return Permission|null Permission object or null if not found
   */
  public static function getPermissionByName($nome)
  {
    $db = new Database('permissions');
    $result = $db->select("nome = '{$nome}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $permission = new self();
    $permData = $result[0];
    $permission->id = $permData->id;
    $permission->nome = $permData->nome;
    $permission->descricao = $permData->descricao;
    $permission->modulo = $permData->modulo;
    $permission->acao = $permData->acao;
    $permission->created_at = $permData->created_at;

    return $permission;
  }

  /**
   * Get all permissions
   *
   * @return array Array of Permission objects
   */
  public static function getAllPermissions()
  {
    $db = new Database('permissions');
    $result = $db->select();

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $permissions = [];
    foreach ($result as $permData) {
      $permission = new self();
      $permission->id = $permData->id;
      $permission->nome = $permData->nome;
      $permission->descricao = $permData->descricao;
      $permission->modulo = $permData->modulo;
      $permission->acao = $permData->acao;
      $permission->created_at = $permData->created_at;
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * Get permissions by module
   *
   * @param string $modulo Module name
   * @return array Array of Permission objects
   */
  public static function getPermissionsByModule($modulo)
  {
    $db = new Database('permissions');
    $result = $db->select("modulo = '{$modulo}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $permissions = [];
    foreach ($result as $permData) {
      $permission = new self();
      $permission->id = $permData->id;
      $permission->nome = $permData->nome;
      $permission->descricao = $permData->descricao;
      $permission->modulo = $permData->modulo;
      $permission->acao = $permData->acao;
      $permission->created_at = $permData->created_at;
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * Create a new permission
   *
   * @return bool Success status
   */
  public function create()
  {
    $this->db->insert([
      'nome' => $this->nome,
      'descricao' => $this->descricao,
      'modulo' => $this->modulo,
      'acao' => $this->acao
    ]);

    return true;
  }

  /**
   * Update permission
   *
   * @return bool Success status
   */
  public function update()
  {
    if (!$this->id) {
      return false;
    }

    $this->db->update([
      'nome' => $this->nome,
      'descricao' => $this->descricao,
      'modulo' => $this->modulo,
      'acao' => $this->acao
    ], "id = {$this->id}");

    return true;
  }

  /**
   * Delete permission
   *
   * @return bool Success status
   */
  public function delete()
  {
    if (!$this->id) {
      return false;
    }

    $this->db->delete("id = {$this->id}");
    return true;
  }
}
