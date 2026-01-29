<?php

namespace Tests\Support;

use App\Domain\Model\Usuario;
use App\Domain\Repository\UsuarioRepositoryInterface;

class FakeUsuarioRepository implements UsuarioRepositoryInterface
{
  /** @var array<int,Usuario> */
  private array $items = [];
  private int $nextId = 1;

  public function findByEmail(string $email): ?Usuario
  {
    foreach ($this->items as $usuario) {
      if ($usuario->email === $email) {
        return $usuario;
      }
    }

    return null;
  }

  public function findById(int $id): ?Usuario
  {
    return $this->items[$id] ?? null;
  }

  /** @return Usuario[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    return array_values($this->items);
  }

  public function count(?string $where = null): int
  {
    return count($this->items);
  }

  public function create(Usuario $usuario): ?int
  {
    $usuario->id = $this->nextId++;
    $this->items[$usuario->id] = $usuario;
    return $usuario->id;
  }

  public function update(Usuario $usuario): bool
  {
    if (!isset($this->items[$usuario->id])) {
      return false;
    }

    $current = $this->items[$usuario->id];
    if ($usuario->nome !== '') {
      $current->nome = $usuario->nome;
    }
    if ($usuario->email !== '') {
      $current->email = $usuario->email;
    }
    if (!empty($usuario->senha)) {
      $current->senha = $usuario->senha;
    }
    if ($usuario->roleId !== null) {
      $current->roleId = $usuario->roleId;
    }

    $this->items[$usuario->id] = $current;
    return true;
  }

  public function delete(int $id): bool
  {
    if (!isset($this->items[$id])) {
      return false;
    }

    unset($this->items[$id]);
    return true;
  }

  public function getDefaultRoleId(): ?int
  {
    return null;
  }
}
