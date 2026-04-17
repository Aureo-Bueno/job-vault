<?php

namespace Tests\Unit;

use App\Application\Service\AuthService;
use App\Domain\Model\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;

class AuthServiceTest extends TestCase
{
  public function testRegisterRejectsInvalidEmail(): void
  {
    $repo = new FakeUserRepository();
    $service = new AuthService($repo);

    $result = $service->register('Nome', 'email-invalido', '123456');

    $this->assertNull($result['user']);
    $this->assertSame('Email inválido', $result['error']);
  }

  public function testRegisterRejectsShortPassword(): void
  {
    $repo = new FakeUserRepository();
    $service = new AuthService($repo);

    $result = $service->register('Nome', 'teste@exemplo.com', '123');

    $this->assertNull($result['user']);
    $this->assertSame('A senha deve ter no mínimo 6 caracteres', $result['error']);
  }

  public function testRegisterRejectsDuplicateEmail(): void
  {
    $repo = new FakeUserRepository();
    $service = new AuthService($repo);

    $repo->create(new User(null, 'Joao', 'joao@exemplo.com', 'hash', null));

    $result = $service->register('Outro', 'joao@exemplo.com', '123456');

    $this->assertNull($result['user']);
    $this->assertSame('O Email digitado já está em uso', $result['error']);
  }

  public function testRegisterCreatesUser(): void
  {
    $repo = new FakeUserRepository();
    $service = new AuthService($repo);

    $result = $service->register('Maria', 'maria@exemplo.com', '123456');

    $this->assertInstanceOf(User::class, $result['user']);
    $this->assertSame('maria@exemplo.com', $result['user']->email);
    $this->assertNull($result['error']);
  }

  public function testAuthenticate(): void
  {
    $repo = new FakeUserRepository();
    $service = new AuthService($repo);

    $user = new User(null, 'Carlos', 'carlos@exemplo.com', password_hash('senha123', PASSWORD_DEFAULT), null);
    $repo->create($user);

    $authenticated = $service->authenticate('carlos@exemplo.com', 'senha123');

    $this->assertInstanceOf(User::class, $authenticated);
  }
}
