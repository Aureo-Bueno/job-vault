<?php

namespace App\Domain\Model;

class Permission
{
  public ?int $id;
  public string $nome;
  public string $descricao;
  public string $modulo;
  public string $acao;
  public string $createdAt;

  public function __construct(
    ?int $id = null,
    string $nome = '',
    string $descricao = '',
    string $modulo = '',
    string $acao = '',
    string $createdAt = ''
  ) {
    $this->id = $id;
    $this->nome = $nome;
    $this->descricao = $descricao;
    $this->modulo = $modulo;
    $this->acao = $acao;
    $this->createdAt = $createdAt;
  }
}
