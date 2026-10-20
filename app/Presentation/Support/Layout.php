<?php

namespace App\Presentation\Support;

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use Throwable;

/**
 * Renders the shared layout. The header receives all its data from here:
 * views do not talk to the container or to the authorization service.
 */
final class Layout
{
  private const ROLE_BADGES = [
    'admin' => 'bg-danger',
    'gestor' => 'bg-warning text-dark',
    'usuario' => 'bg-success',
  ];

  /**
   * @param array<string,mixed> $data
   */
  public static function page(string $view, array $data = []): void
  {
    self::header();
    View::render(VIEW_PATH . $view, $data);
    self::footer();
  }

  public static function header(): void
  {
    View::render(VIEW_PATH . '/layout/header.php', self::navigation());
  }

  public static function footer(): void
  {
    View::render(VIEW_PATH . '/layout/footer.php');
  }

  /**
   * @return array<string,mixed>
   */
  private static function navigation(): array
  {
    $currentRoute = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home';
    $nav = [
      'loggedUser' => null,
      'roleName' => 'Visitante',
      'badgeClass' => 'bg-secondary',
      'hasRole' => false,
      'canViewUsers' => false,
      'canCreateVacancy' => false,
      'canManageVacancies' => false,
      'homeRoute' => 'login',
      'currentRoute' => $currentRoute,
    ];

    try {
      $user = PageAccess::user();
      if ($user === null) {
        return $nav;
      }

      $authorization = AppContainer::authorization();
      $roleName = $authorization->roleName($user->id);

      $nav['loggedUser'] = $user;
      $nav['roleName'] = $roleName ?? 'Sem role';
      $nav['badgeClass'] = self::ROLE_BADGES[$roleName] ?? 'bg-secondary';
      $nav['hasRole'] = $roleName !== null;
      $nav['canViewUsers'] = $authorization->isAdmin($user->id) && $authorization->can($user->id, 'user.list');
      $nav['canCreateVacancy'] = $authorization->can($user->id, 'vacancy.create');
      $nav['canManageVacancies'] = $authorization->can($user->id, PageAccess::MANAGE_VACANCIES);
      $nav['homeRoute'] = $nav['canManageVacancies'] ? 'home' : 'vacancies/apply';
    } catch (Throwable) {
      // Header must render even when the database is down (error pages use it).
    }

    return $nav;
  }
}
