<?php

namespace App\Presentation;

final class View
{
  /**
   * @param array<string,mixed> $data
   */
  public static function render(string $path, array $data = []): void
  {
    if (!empty($data)) {
      extract($data, EXTR_SKIP);
    }

    include $path;
  }

  /**
   * Renders views/partials/<name>.php with its own isolated data.
   *
   * @param array<string,mixed> $data
   */
  public static function partial(string $name, array $data = []): void
  {
    self::render(VIEW_PATH . '/partials/' . $name . '.php', $data);
  }
}
