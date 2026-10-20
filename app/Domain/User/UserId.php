<?php

namespace App\Domain\User;

use App\Domain\Shared\AbstractId;

final class UserId extends AbstractId
{
  protected static function label(): string
  {
    return 'Identificador de usuário';
  }
}
