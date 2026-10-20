<?php

namespace Tests\Unit\Application;

use App\Application\Auth\AuthService;
use App\Application\Users\UserRegistrar;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\User\EmailAlreadyInUseException;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemorySession;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class AuthServiceTest extends TestCase
{
  private InMemoryUserRepository $users;
  private InMemoryRoleRepository $roles;
  private InMemorySession $session;
  private AuthService $auth;

  protected function setUp(): void
  {
    $this->users = new InMemoryUserRepository();
    $this->roles = new InMemoryRoleRepository();
    $this->session = new InMemorySession();
    $this->auth = new AuthService(
      $this->users,
      new UserRegistrar($this->users, $this->roles),
      $this->session,
      new NullLogger()
    );
  }

  public function testRegisterAssignsDefaultRole(): void
  {
    $defaultRole = Role::create(RoleId::generate(), RoleName::defaultUser());
    $this->roles->save($defaultRole);

    $registered = $this->auth->register('Maria', 'MARIA@exemplo.com', '123456');

    $stored = $this->users->get(UserId::fromString($registered->id));
    $this->assertSame('maria@exemplo.com', $registered->email);
    $this->assertTrue($stored?->roleId()?->equals($defaultRole->id()));
  }

  public function testRegisterRejectsInvalidEmail(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->expectExceptionMessage('E-mail inválido.');
    $this->auth->register('Nome', 'email-invalido', '123456');
  }

  public function testRegisterRejectsShortPassword(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->auth->register('Nome', 'teste@exemplo.com', '123');
  }

  public function testRegisterRejectsDuplicateEmail(): void
  {
    $this->auth->register('Joao', 'joao@exemplo.com', '123456');

    $this->expectException(EmailAlreadyInUseException::class);
    $this->auth->register('Outro', 'Joao@Exemplo.com', '123456');
  }

  public function testAuthenticate(): void
  {
    $this->auth->register('Carlos', 'carlos@exemplo.com', 'senha123');

    $this->assertNotNull($this->auth->authenticate('carlos@exemplo.com', 'senha123'));
    $this->assertNull($this->auth->authenticate('carlos@exemplo.com', 'errada'));
    $this->assertNull($this->auth->authenticate('nao-e-email', 'senha123'));
  }

  public function testLoginAndLogoutUseTheSession(): void
  {
    $user = $this->auth->register('Ana', 'ana@exemplo.com', 'senha123');

    $this->auth->login($user);
    $this->assertSame(1, $this->session->regenerations);
    $this->assertSame($user->id, $this->auth->currentUser()?->id);

    $this->auth->logout();
    $this->assertNull($this->auth->currentUser());
  }
}
