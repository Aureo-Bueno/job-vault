<?php

namespace App\Presentation\Support;

use RuntimeException;

final class HttpRedirect
{
  public static function to(string $location): void
  {
    header('Location: ' . $location);

    if (getenv('APP_TEST_MODE') === '1') {
      throw new RuntimeException('REDIRECT:' . $location);
    }

    exit;
  }
}
