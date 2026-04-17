<?php

namespace App\Util;

final class Csrf
{
  private const TOKEN_KEY = '_csrf_token';

  private static function initSession(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }

  public static function token(): string
  {
    self::initSession();

    if (empty($_SESSION[self::TOKEN_KEY])) {
      $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION[self::TOKEN_KEY];
  }

  public static function input(string $field = 'csrf_token'): string
  {
    $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
    $fieldName = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');

    return '<input type="hidden" name="' . $fieldName . '" value="' . $token . '">';
  }

  public static function validate(?string $token): bool
  {
    self::initSession();

    $sessionToken = $_SESSION[self::TOKEN_KEY] ?? null;
    if (!is_string($sessionToken) || !is_string($token) || $token === '') {
      return false;
    }

    return hash_equals($sessionToken, $token);
  }

  public static function validateFromRequest(string $field = 'csrf_token'): bool
  {
    $token = $_POST[$field] ?? null;
    return self::validate(is_string($token) ? $token : null);
  }
}
