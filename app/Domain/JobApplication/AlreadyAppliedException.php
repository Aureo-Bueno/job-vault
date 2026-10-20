<?php

namespace App\Domain\JobApplication;

use App\Domain\Exception\AlreadyExistsException;

final class AlreadyAppliedException extends AlreadyExistsException
{
  public function __construct()
  {
    parent::__construct('Você já se candidatou a esta vaga.');
  }
}
