<?php

namespace App\Presentation\Support;

use App\Infrastructure\Container\AppContainer;

/**
 * Synchronizer-token CSRF protection stored in the session.
 */
final class Csrf
{
  private const TOKEN_KEY = '_csrf_token';
  private const FIELD = 'csrf_token';

  public static function token(): string
  {
    $session = AppContainer::session();
    $token = $session->get(self::TOKEN_KEY);

    if (!is_string($token) || $token === '') {
      $token = bin2hex(random_bytes(32));
      $session->set(self::TOKEN_KEY, $token);
    }

    return $token;
  }

  public static function input(): string
  {
    return '<input type="hidden" name="' . self::FIELD . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
  }

  public static function validate(?string $token): bool
  {
    $sessionToken = AppContainer::session()->get(self::TOKEN_KEY);
    return is_string($sessionToken) && is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
  }

  public static function validateFromRequest(): bool
  {
    $token = $_POST[self::FIELD] ?? null;
    return self::validate(is_string($token) ? $token : null);
  }
}
