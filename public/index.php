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

try {
  require $routes[$route];
} catch (\Throwable $exception) {
  if (getenv('APP_TEST_MODE') === '1' && str_starts_with($exception->getMessage(), 'REDIRECT:')) {
    throw $exception;
  }

  require BASE_PATH . '/vendor/autoload.php';

  $payload = App\Presentation\Support\ExceptionHttpMapper::toPayload($exception);
  http_response_code($payload['httpCode']);

  App\Presentation\View::render(VIEW_PATH . '/layout/header.php');
  echo '<section class="container-content page-section p-3 p-lg-4">';
  echo '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-octagon-fill me-1"></i>' .
    htmlspecialchars($payload['message'], ENT_QUOTES, 'UTF-8') .
    '</div>';
  echo '</section>';
  App\Presentation\View::render(VIEW_PATH . '/layout/footer.php');
  exit;
}
