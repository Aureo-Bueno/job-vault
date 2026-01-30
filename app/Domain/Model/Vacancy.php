<?php

namespace App\Domain\Model;

class Vacancy
{
  public ?string $id;
  public string $title;
  public string $description;
  public string $isActive;
  public string $createdAt;

  public function __construct(
    ?string $id = null,
    string $title = '',
    string $description = '',
    string $isActive = 's',
    string $createdAt = ''
  ) {
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->isActive = $isActive;
    $this->createdAt = $createdAt;
  }
}
