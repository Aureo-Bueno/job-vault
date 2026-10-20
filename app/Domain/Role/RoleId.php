<?php

namespace App\Domain\Role;

use App\Domain\Shared\AbstractId;

final class RoleId extends AbstractId
{
  protected static function label(): string
  {
    return 'Identificador de role';
  }
}
