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

  public static function withSuccess(string $route, ?string $message = null): never
  {
    self::route($route, array_filter(['status' => 'success', 'message' => $message]));
  }

  public static function withError(string $route, Throwable $exception): never
  {
    $payload = ExceptionHttpMapper::toPayload($exception);
    self::route($route, ['status' => $payload['status'], 'message' => $payload['message']]);
  }
}
