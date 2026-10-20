<?php

namespace Tests\Integration\Presentation;

use App\Application\Abstractions\SimpleCommandBus;
use App\Application\Behaviors\AuthorizationBehavior;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Security\AuthenticatedUser;
use App\Application\Security\MessageAuthorizer;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\RedirectSignal;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAuthorization;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemorySession;
use Tests\Support\RecordingHandler;

/**
 * Drives app/Presentation/pages/users-index.php with a real authorization pipeline
 * and fake handlers/persistence.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class UsersAdminHttpRegressionTest extends TestCase
{
  private RecordingHandler $createHandler;
  private RecordingHandler $deleteHandler;

  protected function setUp(): void
  {
    AppContainer::reset();
    putenv('APP_TEST_MODE=1');

    defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__, 3));
    defined('VIEW_PATH') || define('VIEW_PATH', BASE_PATH . '/app/Presentation/views');

    $_GET = [];
    $_POST = [];
    $_SERVER = ['REQUEST_METHOD' => 'POST'];
  }

  protected function tearDown(): void
  {
    AppContainer::reset();
    putenv('APP_TEST_MODE');
  }

  public function testRejectsInvalidCsrf(): void
  {
    $this->bootstrap(['user.create'], ['action' => 'user_create', 'csrf_token' => 'invalid-token']);

    $query = $this->dispatchRoute();

    $this->assertSame('users', $query['r'] ?? null);
    $this->assertSame('validation_error', $query['status'] ?? null);
    $this->assertStringContainsString('Falha na validação da requisição', $query['message'] ?? '');
    $this->assertSame([], $this->createHandler->handled);
  }

  public function testBusRejectsMissingPermissionForDelete(): void
  {
    $this->bootstrap(['user.list'], ['action' => 'user_delete', 'user_id' => '123']);

    $query = $this->dispatchRoute();

    $this->assertSame('forbidden', $query['status'] ?? null);
    $this->assertStringContainsString('user.delete', $query['message'] ?? '');
    $this->assertSame([], $this->deleteHandler->handled);
  }

  public function testCreatesUserWhenPermissionAndCsrfAreValid(): void
  {
    $this->bootstrap(['user.create'], [
      'action' => 'user_create',
      'name' => 'Bea',
      'email' => 'bea@example.com',
      'password' => '123456',
      'role_id' => 'ignored-without-assign-role',
    ]);

    $query = $this->dispatchRoute();

    $this->assertSame('success', $query['status'] ?? null);
    $this->assertStringContainsString('Usuário criado com sucesso', $query['message'] ?? '');
    $this->assertCount(1, $this->createHandler->handled);
    $command = $this->createHandler->handled[0];
    $this->assertInstanceOf(CreateUserCommand::class, $command);
    $this->assertSame('bea@example.com', $command->email);
    $this->assertNull($command->roleId, 'role is only sent when the admin can assign roles');
  }

  public function testUnknownActionIsRejected(): void
  {
    $this->bootstrap(['user.create'], ['action' => 'drop_database']);

    $this->assertSame('Ação inválida.', $this->dispatchRoute()['message'] ?? null);
  }

  /**
   * @param string[] $permissions
   * @param array<string,string> $post
   */
  private function bootstrap(array $permissions, array $post): void
  {
    $session = new InMemorySession();
    $session->set('user', ['id' => '1', 'name' => 'Admin', 'email' => 'admin@example.com']);
    AppContainer::setOverride('session', $session);

    $authorization = new FakeAuthorization($permissions, true);
    AppContainer::setOverride('authorization', $authorization);

    $this->createHandler = new RecordingHandler(
      CreateUserCommand::class,
      new UserDto('10', 'Bea', 'bea@example.com', null)
    );
    $this->deleteHandler = new RecordingHandler(DeleteUserCommand::class);

    $authorizer = new MessageAuthorizer(
      new FakeCurrentUser(new AuthenticatedUser('1', 'Admin', 'admin@example.com')),
      $authorization
    );
    AppContainer::setOverride('commandBus', new SimpleCommandBus(
      [$this->createHandler, $this->deleteHandler],
      [new AuthorizationBehavior([$authorizer, 'authorize'])]
    ));

    $_POST = $post + ['csrf_token' => Csrf::token()];
  }

  /**
   * @return array<string,string>
   */
  private function dispatchRoute(): array
  {
    try {
      require BASE_PATH . '/app/Presentation/pages/users-index.php';
    } catch (RedirectSignal $redirect) {
      parse_str((string) parse_url($redirect->location, PHP_URL_QUERY), $query);
      return $query;
    }

    $this->fail('Expected an HTTP redirect from users-index.php');
  }
}
