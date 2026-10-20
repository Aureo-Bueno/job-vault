<?php

namespace App\Domain\Permission;

use App\Domain\Shared\AbstractId;

final class PermissionId extends AbstractId
{
  protected static function label(): string
  {
    return 'Identificador de permissão';
  }
}
