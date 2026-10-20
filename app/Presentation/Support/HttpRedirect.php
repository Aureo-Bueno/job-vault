<?php

namespace App\Presentation\Support;

use Throwable;

final class HttpRedirect
{
  public static function to(string $location): never
  {
    header('Location: ' . $location);

    if (getenv('APP_TEST_MODE') === '1') {
      throw new RedirectSignal($location);
    }

    exit;
  }

  /**
   * @param array<string,scalar|null> $params
   */
  public static function route(string $route, array $params = []): never
  {
    self::to('index.php?' . http_build_query(['r' => $route] + $params));
  }

  /**
   * @param array<string,scalar|null> $params extra query params (e.g. active tab)
   */
  public static function withSuccess(string $route, ?string $message = null, array $params = []): never
  {
    self::route($route, $params + array_filter(['status' => 'success', 'message' => $message]));
  }

  /**
   * @param array<string,scalar|null> $params extra query params (e.g. active tab)
   */
  public static function withError(string $route, Throwable $exception, array $params = []): never
  {
    $payload = ExceptionHttpMapper::toPayload($exception);
    self::route($route, $params + ['status' => $payload['status'], 'message' => $payload['message']]);
  }
}
