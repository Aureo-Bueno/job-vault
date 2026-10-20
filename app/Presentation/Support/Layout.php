<?php

namespace App\Presentation\Support;

use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Profile\GetMyCandidateProfileQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use Throwable;

/**
 * Renders the shared layout. The header receives all its data from here:
 * views do not talk to the container or to the authorization service.
 */
final class Layout
{
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
    View::render(VIEW_PATH . '/layout/footer.php', self::navigation());
  }

  /**
   * @return array<string,mixed>
   */
  private static function navigation(): array
  {
    static $cache = null;
    return $cache ??= self::buildNavigation();
  }

  /**
   * @return array<string,mixed>
   */
  private static function buildNavigation(): array
  {
    $currentRoute = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home';
    $nav = [
      'loggedUser' => null,
      'roleName' => 'Visitante',
      'hasRole' => false,
      'canViewUsers' => false,
      'canCreateVacancy' => false,
      'canManageVacancies' => false,
      'hasCompany' => false,
      'companyName' => '',
      'profileComplete' => false,
      'homeRoute' => 'login',
      'currentRoute' => $currentRoute,
    ];

    try {
      $user = PageAccess::user();
      if ($user === null) {
        return $nav;
      }

      $authorization = AppContainer::authorization();
      $queryBus = AppContainer::queryBus();
      $roleName = $authorization->roleName($user->id);

      $nav['loggedUser'] = $user;
      $nav['roleName'] = $roleName ?? 'Sem role';
      $nav['hasRole'] = $roleName !== null;
      $nav['canViewUsers'] = $authorization->isAdmin($user->id) && $authorization->can($user->id, 'user.list');
      $nav['canCreateVacancy'] = $authorization->can($user->id, 'vacancy.create');
      $nav['canManageVacancies'] = $authorization->can($user->id, PageAccess::MANAGE_VACANCIES);
      $nav['homeRoute'] = $nav['canManageVacancies'] ? 'home' : 'vacancies/apply';

      if ($nav['canManageVacancies']) {
        $company = $queryBus->ask(new GetMyCompanyQuery());
        if ($company !== null) {
          $nav['hasCompany'] = true;
          $nav['companyName'] = $company->name;
        }
      } else {
        $profile = $queryBus->ask(new GetMyCandidateProfileQuery());
        $nav['profileComplete'] = $profile->isComplete;
      }
    } catch (Throwable) {
      // Header must render even when the database is down (error pages use it).
    }

    return $nav;
  }
}
