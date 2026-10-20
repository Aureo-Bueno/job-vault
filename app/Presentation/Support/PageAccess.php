<?php

namespace App\Presentation\Support;

use App\Application\Exceptions\ForbiddenException;
use App\Application\Security\AuthenticatedUser;
use App\Infrastructure\Container\AppContainer;

/**
 * Page-level access helpers (redirects/visibility). Real enforcement happens in the bus.
 */
final class PageAccess
{
  public const MANAGE_VACANCIES = 'vacancy.manage';

  public static function user(): ?AuthenticatedUser
  {
    return AppContainer::authService()->currentUser();
  }

  public static function requireLogin(): AuthenticatedUser
  {
    $user = self::user();
    if ($user === null) {
      AppContainer::logger('login')->warning('Unauthorized access attempt', [
        'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
      ]);
      HttpRedirect::route('login');
    }

    return $user;
  }

  public static function requireGuest(): void
  {
    if (self::user() !== null) {
      HttpRedirect::route('home');
    }
  }

  public static function can(AuthenticatedUser $user, string $permission): bool
  {
    return AppContainer::authorization()->can($user->id, $permission);
  }

  /**
   * @param string[] $permissions
   */
  public static function canAny(AuthenticatedUser $user, array $permissions): bool
  {
    return AppContainer::authorization()->canAny($user->id, $permissions);
  }

  public static function isAdmin(AuthenticatedUser $user): bool
  {
    return AppContainer::authorization()->isAdmin($user->id);
  }

  public static function requirePermission(AuthenticatedUser $user, string $permission): void
  {
    AppContainer::authorization()->ensure($user->id, $permission);
  }

  public static function requireAdmin(AuthenticatedUser $user): void
  {
    if (!self::isAdmin($user)) {
      throw new ForbiddenException('Acesso restrito ao perfil administrador.');
    }
  }

  /**
   * Managers see the vacancy back-office; everyone else lands on the application page.
   */
  public static function homeRoute(?AuthenticatedUser $user): string
  {
    if ($user === null) {
      return 'login';
    }

    return self::can($user, self::MANAGE_VACANCIES) ? 'home' : 'vacancies/apply';
  }
}
