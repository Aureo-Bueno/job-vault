<?php

namespace App\Infrastructure\Container;

use App\Application\Service\AuthService;
use App\Application\Service\ApplicationService;
use App\Application\Service\RoleService;
use App\Application\Service\UserService;
use App\Application\Service\VacancyService;
use App\Domain\Repository\ApplicationRepositoryInterface;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\VacancyRepositoryInterface;
use App\Infrastructure\Persistence\PdoApplicationRepository;
use App\Infrastructure\Persistence\PdoRolePermissionRepository;
use App\Infrastructure\Persistence\PdoRoleRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoVacancyRepository;

class AppContainer
{
  private static ?VacancyRepositoryInterface $vacancyRepository = null;
  private static ?UserRepositoryInterface $userRepository = null;
  private static ?RoleRepositoryInterface $roleRepository = null;
  private static ?RolePermissionRepositoryInterface $rolePermissionRepository = null;
  private static ?ApplicationRepositoryInterface $applicationRepository = null;

  private static ?VacancyService $vacancyService = null;
  private static ?AuthService $authService = null;
  private static ?RoleService $roleService = null;
  private static ?UserService $userService = null;
  private static ?ApplicationService $applicationService = null;

  public static function vacancyRepository(): VacancyRepositoryInterface
  {
    if (!self::$vacancyRepository) {
      self::$vacancyRepository = new PdoVacancyRepository();
    }

    return self::$vacancyRepository;
  }

  public static function userRepository(): UserRepositoryInterface
  {
    if (!self::$userRepository) {
      self::$userRepository = new PdoUserRepository();
    }

    return self::$userRepository;
  }

  public static function roleRepository(): RoleRepositoryInterface
  {
    if (!self::$roleRepository) {
      self::$roleRepository = new PdoRoleRepository();
    }

    return self::$roleRepository;
  }

  public static function rolePermissionRepository(): RolePermissionRepositoryInterface
  {
    if (!self::$rolePermissionRepository) {
      self::$rolePermissionRepository = new PdoRolePermissionRepository();
    }

    return self::$rolePermissionRepository;
  }

  public static function applicationRepository(): ApplicationRepositoryInterface
  {
    if (!self::$applicationRepository) {
      self::$applicationRepository = new PdoApplicationRepository();
    }

    return self::$applicationRepository;
  }

  public static function vacancyService(): VacancyService
  {
    if (!self::$vacancyService) {
      self::$vacancyService = new VacancyService(self::vacancyRepository());
    }

    return self::$vacancyService;
  }

  public static function authService(): AuthService
  {
    if (!self::$authService) {
      self::$authService = new AuthService(self::userRepository());
    }

    return self::$authService;
  }

  public static function roleService(): RoleService
  {
    if (!self::$roleService) {
      self::$roleService = new RoleService(
        self::userRepository(),
        self::roleRepository(),
        self::rolePermissionRepository()
      );
    }

    return self::$roleService;
  }

  public static function userService(): UserService
  {
    if (!self::$userService) {
      self::$userService = new UserService(self::userRepository());
    }

    return self::$userService;
  }

  public static function applicationService(): ApplicationService
  {
    if (!self::$applicationService) {
      self::$applicationService = new ApplicationService(self::applicationRepository());
    }

    return self::$applicationService;
  }
}
