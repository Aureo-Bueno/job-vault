<?php

namespace App\Infrastructure\Http;

use App\Application\Ports\SessionInterface;

/**
 * SessionInterface backed by the native PHP session with secure cookie defaults.
 *
 * start() should be called by the front controller before any output is sent.
 */
final class NativeSession implements SessionInterface
{
  public function start(): void
  {
    if (session_status() === PHP_SESSION_ACTIVE) {
      return;
    }

    if (headers_sent($file, $line)) {
      error_log(sprintf('Session not started: output already sent at %s:%d', $file, $line));
      return;
    }

    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => self::isSecureRequest(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    session_start();
  }

  public function get(string $key, mixed $default = null): mixed
  {
    $this->start();
    return $_SESSION[$key] ?? $default;
  }

  public function set(string $key, mixed $value): void
  {
    $this->start();
    $_SESSION[$key] = $value;
  }

  public function remove(string $key): void
  {
    $this->start();
    unset($_SESSION[$key]);
  }

  public function regenerate(): void
  {
    $this->start();
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
      session_regenerate_id(true);
    }
  }

  public function destroy(): void
  {
    $this->start();
    $_SESSION = [];

    if (ini_get('session.use_cookies') && !headers_sent()) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
      ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }
  }

  private static function isSecureRequest(): bool
  {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
  }
}
