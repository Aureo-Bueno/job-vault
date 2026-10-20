<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Role\RoleId;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

final class SharedValueObjectsTest extends TestCase
{
  public function testIdsAcceptUuidAndDigits(): void
  {
    $this->assertSame('42', UserId::fromString('42')->value());
    $this->assertSame(
      'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
      UserId::fromString('A0EEBC99-9C0B-4EF8-BB6D-6BB9BD380A11')->value()
    );
    $this->assertTrue(UserId::isValid(UserId::generate()->value()));
  }

  public function testIdRejectsGarbage(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->expectExceptionMessage('Identificador de role inválido.');
    RoleId::fromString("1 OR 1=1");
  }

  public function testIdsOfDifferentTypesAreNotEqual(): void
  {
    $this->assertFalse(UserId::fromString('1')->equals(RoleId::fromString('1')));
    $this->assertTrue(UserId::fromString('1')->equals(UserId::fromString('1')));
  }

  public function testPageRequestClampsToLastPage(): void
  {
    $page = (new PageRequest(10, 5))->clampTo(12);

    $this->assertSame(3, $page->number);
    $this->assertSame(10, $page->offset());
    $this->assertSame(1, (new PageRequest(4, 5))->clampTo(0)->number);
  }

  public function testSearchTerm(): void
  {
    $this->assertNull(SearchTerm::tryFrom('   '));

    $term = SearchTerm::tryFrom(' dev php ');
    $this->assertSame(['dev', 'php'], $term?->words());
    $this->assertTrue($term?->matches('Vaga DEV PHP sênior'));
  }
}
