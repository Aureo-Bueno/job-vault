<?php

/**
 * Logout endpoint controller.
 *
 * Terminates the current session and redirects through AuthService.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;

$authService = AppContainer::authService();
$authService->logout();
