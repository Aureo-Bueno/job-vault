<?php

namespace Tests\Unit;

use App\Application\Service\UserService;
use App\Domain\Model\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;

class UserServiceTest extends TestCase
{
  public function testCreateHashesPassword(): void
  {
    $repo = new FakeUserRepository();
    $service = new UserService($repo);

    $user = new User(null, 'Ana', 'ana@exemplo.com', '', null);
    $created = $service->create($user, 'minhaSenha');

    $this->assertNotNull($created);
    $this->assertNotSame('minhaSenha', $created->password);
    $this->assertTrue(password_verify('minhaSenha', $created->password));
  }

  public function testUpdateWithPasswordChangesHash(): void
  {
    $repo = new FakeUserRepository();
    $service = new UserService($repo);

    $user = new User(null, 'Bob', 'bob@exemplo.com', password_hash('old', PASSWORD_DEFAULT), null);
    $repo->create($user);

    $user->name = 'Bob Novo';
    $service->update($user, 'novaSenha');

    $updated = $repo->findById((string) $user->id);
    $this->assertNotNull($updated);
    $this->assertTrue(password_verify('novaSenha', $updated->password));
  }
}
