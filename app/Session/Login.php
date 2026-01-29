<?php

namespace App\Session;

use App\Util\Logger;

/**
 * Login Session Manager
 *
 * Handles user authentication, session management, and authorization.
 * Provides static methods for login/logout and session verification.
 *
 * @package App\Session
 * @version 2.0
 */
class Login
{
  /**
   * Application logger instance
   *
   * @var Logger
   */
  private static $logger;

  /**
   * Initialize logger
   *
   * @return void
   */
  private static function initLogger()
  {
    if (!isset(self::$logger)) {
      self::$logger = new Logger('login');
    }
  }

  /**
   * Initialize PHP session if not already started
   *
   * Safely starts session without triggering headers already sent errors.
   *
   * @return void
   */
  private static function init()
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }

  /**
   * Get logged-in user data
   *
   * Returns array with user ID, name, and email.
   * Returns null if no user is logged in.
   *
   * Example:
   *   $usuario = Login::getUsuarioLogado();
   *   if ($usuario) {
   *       echo "Bem-vindo, " . $usuario['nome'];
   *   }
   *
   * @return array|null User data or null if not logged in
   */
  public static function getUsuarioLogado()
  {
    self::init();
    return self::isLogged() ? $_SESSION['usuario'] : null;
  }

  /**
   * Log in a user
   *
   * Creates session with user data and redirects to index.
   *
   * @param object $obUsuario User object with id, nome, email, role_id properties
   * @return void Redirects to index.php
   */
  public static function login($obUsuario)
  {
    self::init();

    // Store user data in session
    $_SESSION['usuario'] = [
      'id' => $obUsuario->id,
      'nome' => $obUsuario->nome,
      'email' => $obUsuario->email,
      'role_id' => $obUsuario->role_id ?? null  // ← Handle null safely
    ];

    // Log successful login AFTER redirect is sent (in background)
    register_shutdown_function(function () use ($obUsuario) {
      self::initLogger();
      self::$logger->info('User logged in', [
        'user_id' => $obUsuario->id,
        'email' => $obUsuario->email,
        'role_id' => $obUsuario->role_id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
    });

    // Redirect to home (must be BEFORE any output)
    header('Location: index.php');
    exit;
  }

  /**
   * Log out the current user
   *
   * Destroys user session and redirects to login.
   *
   * @return void Redirects to login.php
   */
  public static function logout()
  {
    self::init();

    $userEmail = $_SESSION['usuario']['email'] ?? 'unknown';

    // Remove user session
    unset($_SESSION['usuario']);

    // Log logout AFTER redirect (in background)
    register_shutdown_function(function () use ($userEmail) {
      self::initLogger();
      self::$logger->info('User logged out', [
        'email' => $userEmail,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
    });

    // Redirect to login (must be BEFORE any output)
    header('Location: login.php');
    exit;
  }

  /**
   * Check if user is currently logged in
   *
   * Verifies presence of user session data.
   *
   * @return bool True if user is logged in
   */
  public static function isLogged()
  {
    self::init();
    return isset($_SESSION['usuario']['id']);
  }

  /**
   * Require user to be logged in
   *
   * If user is not logged in, redirects to login page.
   * Useful at the beginning of protected pages.
   *
   * Usage:
   *   Login::requireLogin();
   *   // Code here only runs if user is logged in
   *
   * @return void Redirects to login.php if not logged in
   */
  public static function requireLogin()
  {
    if (!self::isLogged()) {
      self::initLogger();
      self::$logger->warning('Unauthorized access attempt', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
      ]);

      header('Location: login.php');
      exit;
    }
  }

  /**
   * Require user to be logged out
   *
   * If user is logged in, redirects to home page.
   * Useful for login/register pages to prevent logged-in users from accessing them.
   *
   * Usage:
   *   Login::requireLogout();
   *   // Code here only runs if user is NOT logged in
   *
   * @return void Redirects to index.php if already logged in
   */
  public static function requireLogout()
  {
    if (self::isLogged()) {
      header('Location: index.php');
      exit;
    }
  }

  /**
   * Check if user is an administrator
   *
   * Can be extended to check user role from database.
   *
   * @return bool True if user is admin
   */
  public static function isAdmin()
  {
    self::init();
    // TODO: Implement role checking from database
    return isset($_SESSION['usuario']['role']) && $_SESSION['usuario']['role'] === 'admin';
  }
}
