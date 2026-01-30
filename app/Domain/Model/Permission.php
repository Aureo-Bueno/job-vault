<?php

namespace App\Domain\Model;

class Permission
{
  public ?string $id;
  public string $name;
  public string $description;
  public string $module;
  public string $action;
  public string $createdAt;

  public function __construct(
    ?string $id = null,
    string $name = '',
    string $description = '',
    string $module = '',
    string $action = '',
    string $createdAt = ''
  ) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->module = $module;
    $this->action = $action;
    $this->createdAt = $createdAt;
  }
}
