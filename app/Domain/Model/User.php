<?php

namespace App\Domain\Model;

class User
{
  public ?string $id;
  public string $name;
  public string $email;
  public string $password;
  public ?string $roleId;

  public function __construct(
    ?string $id = null,
    string $name = '',
    string $email = '',
    string $password = '',
    ?string $roleId = null
  ) {
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->password = $password;
    $this->roleId = $roleId;
  }
}
