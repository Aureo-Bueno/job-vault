<?php

namespace App\Util;

class IdValidator
{
  public static function isValid($id): bool
  {
    if ($id === null) {
      return false;
    }

    $id = (string) $id;
    if ($id === '') {
      return false;
    }

    if (ctype_digit($id)) {
      return true;
    }

    return (bool) preg_match(
      '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
      $id
    );
  }
}
