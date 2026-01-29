<?php

define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/app/Presentation/views');
define('PAGE_PATH', BASE_PATH . '/app/Presentation/pages');

$routes = require BASE_PATH . '/routes.php';
$route = $_GET['r'] ?? 'home';

if (!isset($routes[$route])) {
  http_response_code(404);
  require BASE_PATH . '/vendor/autoload.php';

  App\Presentation\View::render(VIEW_PATH . '/layout/header.php');
  App\Presentation\View::render(VIEW_PATH . '/pages/404.php');
  App\Presentation\View::render(VIEW_PATH . '/layout/footer.php');
  exit;
}

require $routes[$route];
