<?php

namespace App\Domain\Model;

class Vaga
{
  public ?int $id;
  public string $titulo;
  public string $descricao;
  public string $ativo;
  public string $data;

  public function __construct(
    ?int $id = null,
    string $titulo = '',
    string $descricao = '',
    string $ativo = 's',
    string $data = ''
  ) {
    $this->id = $id;
    $this->titulo = $titulo;
    $this->descricao = $descricao;
    $this->ativo = $ativo;
    $this->data = $data;
  }
}
