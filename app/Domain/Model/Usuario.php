<?php

namespace App\Domain\Model;

class Usuario
{
  public ?int $id;
  public string $nome;
  public string $email;
  public string $senha;
  public ?int $roleId;

  public function __construct(
    ?int $id = null,
    string $nome = '',
    string $email = '',
    string $senha = '',
    ?int $roleId = null
  ) {
    $this->id = $id;
    $this->nome = $nome;
    $this->email = $email;
    $this->senha = $senha;
    $this->roleId = $roleId;
  }
}
