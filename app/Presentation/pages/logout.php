<?php

/**
 * Ends the session and goes back to the login page.
 */

use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\HttpRedirect;

AppContainer::authService()->logout();
HttpRedirect::route('login');
