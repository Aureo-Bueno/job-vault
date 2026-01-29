<?php

namespace App\Application\Service;

use App\Domain\Model\Usuario;
use App\Domain\Repository\UsuarioRepositoryInterface;

class UsuarioService
{
  private UsuarioRepositoryInterface $usuarioRepository;

  public function __construct(UsuarioRepositoryInterface $usuarioRepository)
  {
    $this->usuarioRepository = $usuarioRepository;
  }

  /** @return Usuario[] */
  public function list(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    return $this->usuarioRepository->findAll($where, $order, $limit);
  }

  public function count(?string $where = null): int
  {
    return $this->usuarioRepository->count($where);
  }

  public function getById(int $id): ?Usuario
  {
    return $this->usuarioRepository->findById($id);
  }

  public function getByEmail(string $email): ?Usuario
  {
    return $this->usuarioRepository->findByEmail($email);
  }

  public function create(Usuario $usuario, string $senhaPlano): ?Usuario
  {
    $usuario->senha = password_hash($senhaPlano, PASSWORD_DEFAULT);
    $id = $this->usuarioRepository->create($usuario);
    if (!$id) {
      return null;
    }

    $usuario->id = $id;
    return $usuario;
  }

  public function update(Usuario $usuario, ?string $senhaPlano = null): bool
  {
    if ($senhaPlano !== null && $senhaPlano !== '') {
      $usuario->senha = password_hash($senhaPlano, PASSWORD_DEFAULT);
      return $this->usuarioRepository->update($usuario);
    }

    return $this->usuarioRepository->update($usuario);
  }

  public function delete(int $id): bool
  {
    return $this->usuarioRepository->delete($id);
  }
}
