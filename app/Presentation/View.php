<?php

namespace App\Presentation;

final class View
{
  public static function render(string $path, array $data = []): void
  {
    if (!empty($data)) {
      extract($data, EXTR_SKIP);
    }

    include $path;
  }
}
