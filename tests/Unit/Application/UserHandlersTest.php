<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Exceptions\NotFoundException;
use App\Application\Features\Users\CreateUserHandler;
use App\Application\Features\Users\DeleteUserHandler;
use App\Application\Features\Users\GetUserByIdHandler;
use App\Application\Features\Users\SearchUsersHandler;
use App\Application\Features\Users\UpdateUserHandler;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Queries\Users\SearchUsersQuery;
use App\Application\Users\UserRegistrar;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\User\EmailAlreadyInUseException;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemoryUserRepository;

final class UserHandlersTest extends TestCase
{
  private InMemoryUserRepository $users;
  private InMemoryRoleRepository $roles;
  private UserRegistrar $registrar;

  protected function setUp(): void
  {
    $this->users = new InMemoryUserRepository();
    $this->roles = new InMemoryRoleRepository();
    $this->registrar = new UserRegistrar($this->users, $this->roles);
  }

  public function testCreateUserReturnsDto(): void
  {
    $result = $this->create('Ana', 'ana@example.com');

    $this->assertInstanceOf(UserDto::class, $result);
    $this->assertSame('Ana', $result->name);
  }

  public function testCreateUserWithUnknownRoleFails(): void
  {
    $this->expectException(NotFoundException::class);
    (new CreateUserHandler($this->registrar))->handle(new CreateUserCommand('Ana', 'ana@example.com', '123456', '999'));
  }

  public function testUpdateUserChangesProfilePasswordAndRole(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $this->roles->save($role);
    $created = $this->create('Bob', 'bob@example.com');

    (new UpdateUserHandler($this->users, $this->registrar))->handle(new UpdateUserCommand(
      $created->id,
      'Bob Novo',
      'bob.novo@example.com',
      'novaSenha',
      $role->id()->value(),
      true
    ));

    $user = $this->users->get(UserId::fromString($created->id));
    $this->assertSame('Bob Novo', $user?->name()->value());
    $this->assertTrue($user?->verifyPassword('novaSenha'));
    $this->assertTrue($user?->roleId()?->equals($role->id()));
  }

  public function testUpdateKeepsRoleWhenNotRequested(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $this->roles->save($role);
    $created = (new CreateUserHandler($this->registrar))->handle(
      new CreateUserCommand('Bob', 'bob@example.com', '123456', $role->id()->value())
    );

    (new UpdateUserHandler($this->users, $this->registrar))->handle(
      new UpdateUserCommand($created->id, 'Bob', 'bob@example.com')
    );

    $this->assertTrue($this->users->get(UserId::fromString($created->id))?->roleId()?->equals($role->id()));
  }

  public function testUpdateRejectsEmailOfAnotherUser(): void
  {
    $this->create('A', 'a@example.com');
    $b = $this->create('B', 'b@example.com');

    $this->expectException(EmailAlreadyInUseException::class);
    (new UpdateUserHandler($this->users, $this->registrar))->handle(
      new UpdateUserCommand($b->id, 'B', 'a@example.com')
    );
  }

  public function testUpdateUnknownUser(): void
  {
    $this->expectException(NotFoundException::class);
    (new UpdateUserHandler($this->users, $this->registrar))->handle(
      new UpdateUserCommand('404', 'Nome', 'novo@example.com')
    );
  }

  public function testDeleteUser(): void
  {
    $created = $this->create('Bea', 'bea@example.com');

    (new DeleteUserHandler($this->users, FakeCurrentUser::as('1')))->handle(new DeleteUserCommand($created->id));

    $this->assertNull($this->users->get(UserId::fromString($created->id)));
  }

  public function testCannotDeleteYourself(): void
  {
    $created = $this->create('Bea', 'bea@example.com');

    $this->expectException(BusinessRuleViolationException::class);
    (new DeleteUserHandler($this->users, FakeCurrentUser::as($created->id)))->handle(new DeleteUserCommand($created->id));
  }

  public function testSearchAndGet(): void
  {
    $this->create('Ana Lima', 'ana@example.com');
    $this->create('Bruno', 'bruno@example.com');
    $carlos = $this->create('Carlos', 'carlos@example.com');

    $result = (new SearchUsersHandler($this->users))->handle(new SearchUsersQuery('ana', null, 1, 10));
    $this->assertSame(1, $result->total);
    $this->assertSame('Ana Lima', $result->items[0]->name);

    $paged = (new SearchUsersHandler($this->users))->handle(new SearchUsersQuery(null, null, 9, 2));
    $this->assertSame(3, $paged->total);
    $this->assertSame(2, $paged->page, 'page is clamped to the last one');
    $this->assertCount(1, $paged->items);

    $get = new GetUserByIdHandler($this->users);
    $this->assertSame('Carlos', $get->handle(new GetUserByIdQuery($carlos->id))?->name);
    $this->assertNull($get->handle(new GetUserByIdQuery('not-an-id')));
  }

  private function create(string $name, string $email): UserDto
  {
    return (new CreateUserHandler($this->registrar))->handle(new CreateUserCommand($name, $email, '123456'));
  }
}
