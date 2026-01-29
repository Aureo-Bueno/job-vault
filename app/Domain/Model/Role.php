<?php

namespace App\Domain\Model;

class Role
{
  public ?int $id;
  public string $nome;
  public string $descricao;
  public string $createdAt;

  public function __construct(
    ?int $id = null,
    string $nome = '',
    string $descricao = '',
    string $createdAt = ''
  ) {
    $this->id = $id;
    $this->nome = $nome;
    $this->descricao = $descricao;
    $this->createdAt = $createdAt;
  }
}
