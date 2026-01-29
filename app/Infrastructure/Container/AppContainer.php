<?php

namespace App\Infrastructure\Container;

use App\Application\Service\AuthService;
use App\Application\Service\RoleService;
use App\Application\Service\UsuarioService;
use App\Application\Service\VagaService;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UsuarioRepositoryInterface;
use App\Domain\Repository\VagaRepositoryInterface;
use App\Infrastructure\Persistence\PdoRolePermissionRepository;
use App\Infrastructure\Persistence\PdoRoleRepository;
use App\Infrastructure\Persistence\PdoUsuarioRepository;
use App\Infrastructure\Persistence\PdoVagaRepository;

class AppContainer
{
  private static ?VagaRepositoryInterface $vagaRepository = null;
  private static ?UsuarioRepositoryInterface $usuarioRepository = null;
  private static ?RoleRepositoryInterface $roleRepository = null;
  private static ?RolePermissionRepositoryInterface $rolePermissionRepository = null;

  private static ?VagaService $vagaService = null;
  private static ?AuthService $authService = null;
  private static ?RoleService $roleService = null;
  private static ?UsuarioService $usuarioService = null;

  public static function vagaRepository(): VagaRepositoryInterface
  {
    if (!self::$vagaRepository) {
      self::$vagaRepository = new PdoVagaRepository();
    }

    return self::$vagaRepository;
  }

  public static function usuarioRepository(): UsuarioRepositoryInterface
  {
    if (!self::$usuarioRepository) {
      self::$usuarioRepository = new PdoUsuarioRepository();
    }

    return self::$usuarioRepository;
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

  public static function vagaService(): VagaService
  {
    if (!self::$vagaService) {
      self::$vagaService = new VagaService(self::vagaRepository());
    }

    return self::$vagaService;
  }

  public static function authService(): AuthService
  {
    if (!self::$authService) {
      self::$authService = new AuthService(self::usuarioRepository());
    }

    return self::$authService;
  }

  public static function roleService(): RoleService
  {
    if (!self::$roleService) {
      self::$roleService = new RoleService(
        self::usuarioRepository(),
        self::roleRepository(),
        self::rolePermissionRepository()
      );
    }

    return self::$roleService;
  }

  public static function usuarioService(): UsuarioService
  {
    if (!self::$usuarioService) {
      self::$usuarioService = new UsuarioService(self::usuarioRepository());
    }

    return self::$usuarioService;
  }
}
