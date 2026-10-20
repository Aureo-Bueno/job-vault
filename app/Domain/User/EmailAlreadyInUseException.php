<?php

namespace App\Domain\User;

use App\Domain\Exception\AlreadyExistsException;

final class EmailAlreadyInUseException extends AlreadyExistsException
{
  public function __construct()
  {
    parent::__construct('O e-mail informado já está em uso.');
  }
}
