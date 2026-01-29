<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;

$authService = AppContainer::authService();
$authService->logout();
