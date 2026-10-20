<?php

define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/app/Presentation/views');
define('PAGE_PATH', BASE_PATH . '/app/Presentation/pages');

require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Infrastructure\Http\NativeSession;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\Layout;
use App\Presentation\Support\RedirectSignal;

// Start the session before any output so cookies/headers can still be sent.
$session = AppContainer::session();
if ($session instanceof NativeSession) {
  $session->start();
}

$routes = require BASE_PATH . '/routes.php';
$route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home';

if (!isset($routes[$route])) {
  http_response_code(404);
  Layout::page('/pages/404.php');
  exit;
}

try {
  require $routes[$route];
} catch (RedirectSignal $signal) {
  throw $signal;
} catch (\Throwable $exception) {
  $payload = ExceptionHttpMapper::toPayload($exception);

  if (ExceptionHttpMapper::isUnexpected($exception)) {
    AppContainer::logger('app')->error('Unhandled exception', [
      'route' => $route,
      'exception' => $exception::class,
      'error' => $exception->getMessage(),
      'previous' => $exception->getPrevious()?->getMessage(),
      'at' => $exception->getFile() . ':' . $exception->getLine(),
    ]);
  }

  http_response_code($payload['httpCode']);
  Layout::page('/pages/error.php', ['message' => $payload['message']]);
}
