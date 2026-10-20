<?php

namespace App\Application\Security;

/**
 * Gives use cases access to "who is executing this" without knowing about HTTP/sessions.
 */
interface CurrentUserProvider
{
  public function currentUser(): ?AuthenticatedUser;
}
