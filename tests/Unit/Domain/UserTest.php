<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Role\RoleId;
use App\Domain\User\EmailAddress;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
  public function testRegisterHashesPassword(): void
  {
    $user = $this->user('segredo1');

    $this->assertNotSame('segredo1', $user->passwordHash()->value());
    $this->assertTrue($user->verifyPassword('segredo1'));
    $this->assertFalse($user->verifyPassword('outra'));
  }

  public function testChangePasswordAndProfile(): void
  {
    $user = $this->user('segredo1');

    $user->changePassword(PlainPassword::fromString('nova-senha'));
    $user->changeProfile(PersonName::fromString('Ana Maria'), EmailAddress::fromString('ana.maria@example.com'));

    $this->assertTrue($user->verifyPassword('nova-senha'));
    $this->assertSame('Ana Maria', $user->name()->value());
    $this->assertSame('ana.maria@example.com', $user->email()->value());
  }

  public function testAssignAndRemoveRole(): void
  {
    $user = $this->user('segredo1');
    $roleId = RoleId::generate();

    $user->assignRole($roleId);
    $this->assertTrue($user->roleId()?->equals($roleId));

    $user->assignRole(null);
    $this->assertFalse($user->hasRole());
  }

  public function testEmailIsNormalized(): void
  {
    $this->assertSame('ana@example.com', EmailAddress::fromString('  ANA@Example.com ')->value());
    $this->assertNull(EmailAddress::tryFrom('invalido'));
  }

  public function testPasswordPolicy(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->expectExceptionMessage('no mínimo 6');
    PlainPassword::fromString('123');
  }

  public function testNameIsRequired(): void
  {
    $this->expectException(InvalidValueException::class);
    PersonName::fromString('  ');
  }

  private function user(string $password): User
  {
    return User::register(
      UserId::generate(),
      PersonName::fromString('Ana'),
      EmailAddress::fromString('ana@example.com'),
      PlainPassword::fromString($password)
    );
  }
}
