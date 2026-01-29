<?php

namespace Tests\Unit;

use App\Application\Service\UsuarioService;
use App\Domain\Model\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUsuarioRepository;

class UsuarioServiceTest extends TestCase
{
  public function testCreateHashesPassword(): void
  {
    $repo = new FakeUsuarioRepository();
    $service = new UsuarioService($repo);

    $usuario = new Usuario(null, 'Ana', 'ana@exemplo.com', '', null);
    $created = $service->create($usuario, 'minhaSenha');

    $this->assertNotNull($created);
    $this->assertNotSame('minhaSenha', $created->senha);
    $this->assertTrue(password_verify('minhaSenha', $created->senha));
  }

  public function testUpdateWithPasswordChangesHash(): void
  {
    $repo = new FakeUsuarioRepository();
    $service = new UsuarioService($repo);

    $usuario = new Usuario(null, 'Bob', 'bob@exemplo.com', password_hash('old', PASSWORD_DEFAULT), null);
    $repo->create($usuario);

    $usuario->nome = 'Bob Novo';
    $service->update($usuario, 'novaSenha');

    $updated = $repo->findById($usuario->id);
    $this->assertTrue(password_verify('novaSenha', $updated->senha));
  }
}
