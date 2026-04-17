<?php

namespace Tests\Unit\Application\Features;

use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Features\Users\CountUsersHandler;
use App\Application\Features\Users\CreateUserHandler;
use App\Application\Features\Users\DeleteUserHandler;
use App\Application\Features\Users\GetUserByEmailHandler;
use App\Application\Features\Users\ListUsersHandler;
use App\Application\Features\Users\UpdateUserHandler;
use App\Application\Queries\Users\CountUsersQuery;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Application\Queries\Users\ListUsersQuery;
use App\Application\Service\UserService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;

class UserHandlersTest extends TestCase
{
  public function testCreateUserHandlerCreatesDto(): void
  {
    $service = new UserService(new FakeUserRepository());
    $handler = new CreateUserHandler($service);

    $result = $handler->handle(new CreateUserCommand(
      'Ana',
      'ana@example.com',
      '123456',
      null
    ));

    $this->assertInstanceOf(UserDto::class, $result);
    $this->assertSame('Ana', $result->name);
    $this->assertSame('ana@example.com', $result->email);
  }

  public function testUpdateUserHandlerReturnsFalseWhenUserDoesNotExist(): void
  {
    $service = new UserService(new FakeUserRepository());
    $handler = new UpdateUserHandler($service);

    $updated = $handler->handle(new UpdateUserCommand(
      '404',
      'Novo Nome',
      'novo@example.com',
      null,
      null
    ));

    $this->assertFalse($updated);
  }

  public function testDeleteUserHandlerDeletesExistingUser(): void
  {
    $repository = new FakeUserRepository();
    $service = new UserService($repository);

    $createHandler = new CreateUserHandler($service);
    $created = $createHandler->handle(new CreateUserCommand(
      'Bea',
      'bea@example.com',
      '123456',
      null
    ));

    $deleteHandler = new DeleteUserHandler($service);
    $deleted = $deleteHandler->handle(new DeleteUserCommand((string) $created?->id));

    $this->assertTrue($deleted);
    $this->assertNull($repository->findById((string) $created?->id));
  }

  public function testGetUserByEmailHandlerReturnsDto(): void
  {
    $service = new UserService(new FakeUserRepository());
    $createHandler = new CreateUserHandler($service);
    $createHandler->handle(new CreateUserCommand(
      'Carlos',
      'carlos@example.com',
      '123456',
      null
    ));

    $handler = new GetUserByEmailHandler($service);
    $result = $handler->handle(new GetUserByEmailQuery('carlos@example.com'));

    $this->assertInstanceOf(UserDto::class, $result);
    $this->assertSame('Carlos', $result->name);
  }

  public function testCountAndListHandlersReturnExpectedValues(): void
  {
    $service = new UserService(new FakeUserRepository());
    $createHandler = new CreateUserHandler($service);

    $createHandler->handle(new CreateUserCommand('U1', 'u1@example.com', '123456', null));
    $createHandler->handle(new CreateUserCommand('U2', 'u2@example.com', '123456', null));

    $countHandler = new CountUsersHandler($service);
    $listHandler = new ListUsersHandler($service);

    $count = $countHandler->handle(new CountUsersQuery());
    $users = $listHandler->handle(new ListUsersQuery());

    $this->assertSame(2, $count);
    $this->assertCount(2, $users);
    $this->assertContainsOnlyInstancesOf(UserDto::class, $users);
  }
}
