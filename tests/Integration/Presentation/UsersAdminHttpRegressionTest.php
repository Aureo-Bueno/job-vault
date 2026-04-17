<?php

namespace Tests\Integration\Presentation;

use App\Application\Commands\Users\CreateUserCommand;
use App\Application\DTOs\UserDto;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Infrastructure\Container\AppContainer;
use App\Util\Csrf;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeAuthService;
use Tests\Support\FakeCommandBus;
use Tests\Support\FakeQueryBus;
use Tests\Support\FakeRoleService;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UsersAdminHttpRegressionTest extends TestCase
{
  protected function setUp(): void
  {
    AppContainer::reset();
    putenv('APP_TEST_MODE=1');

    if (!defined('BASE_PATH')) {
      define('BASE_PATH', dirname(__DIR__, 3));
    }

    if (!defined('VIEW_PATH')) {
      define('VIEW_PATH', BASE_PATH . '/app/Presentation/views');
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    $_GET = [];
    $_POST = [];
    $_SERVER = [];
    $_SESSION = [];
  }

  protected function tearDown(): void
  {
    AppContainer::reset();
    putenv('APP_TEST_MODE');

    if (session_status() === PHP_SESSION_ACTIVE) {
      $_SESSION = [];
      session_destroy();
    }
  }

  public function testUsersActionRejectsInvalidCsrf(): void
  {
    $this->bootstrapUsersRoute(
      ['user.create'],
      [
        'action' => 'user_create',
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => '123456',
        'csrf_token' => 'invalid-token',
      ]
    );

    $location = $this->executeUsersRoute();
    $query = $this->parseRedirectQuery($location);

    $this->assertSame('users', $query['r'] ?? null);
    $this->assertSame('error', $query['status'] ?? null);
    $this->assertStringContainsString('Falha na validação da requisição', (string) ($query['message'] ?? ''));
  }

  public function testUsersActionRejectsMissingPermissionForDelete(): void
  {
    $csrfToken = $this->bootstrapUsersRoute(
      ['user.list'],
      [
        'action' => 'user_delete',
        'user_id' => '123',
      ]
    );

    $_POST['csrf_token'] = $csrfToken;

    $location = $this->executeUsersRoute();
    $query = $this->parseRedirectQuery($location);

    $this->assertSame('error', $query['status'] ?? null);
    $this->assertStringContainsString('não tem permissão para excluir usuários', (string) ($query['message'] ?? ''));
  }

  public function testUsersActionCreatesUserWhenPermissionAndCsrfAreValid(): void
  {
    $recordedCommands = [];
    $csrfToken = $this->bootstrapUsersRoute(
      ['user.create'],
      [
        'action' => 'user_create',
        'name' => 'Bea',
        'email' => 'bea@example.com',
        'password' => '123456',
      ],
      $recordedCommands
    );

    $_POST['csrf_token'] = $csrfToken;

    $location = $this->executeUsersRoute();
    $query = $this->parseRedirectQuery($location);

    $this->assertSame('success', $query['status'] ?? null);
    $this->assertStringContainsString('Usuário criado com sucesso', (string) ($query['message'] ?? ''));
    $this->assertCount(1, $recordedCommands);
    $this->assertInstanceOf(CreateUserCommand::class, $recordedCommands[0]);
    $this->assertSame('bea@example.com', $recordedCommands[0]->email);
  }

  /**
   * @param string[] $permissions
   * @param array<string,mixed> $postData
   * @param array<int,object> $recordedCommands
   */
  private function bootstrapUsersRoute(array $permissions, array $postData, array &$recordedCommands = []): string
  {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = $postData;

    $_SESSION['user'] = [
      'id' => '1',
      'name' => 'Admin',
      'email' => 'admin@example.com',
      'role_id' => 'admin-role',
    ];

    $csrfToken = Csrf::token();

    AppContainer::setOverride('authService', new FakeAuthService(true, $_SESSION['user']));
    AppContainer::setOverride('roleService', new FakeRoleService($permissions, true, false));

    AppContainer::setOverride('queryBus', new FakeQueryBus(function ($query) {
      if ($query instanceof GetUserByEmailQuery) {
        return null;
      }

      return [];
    }));

    AppContainer::setOverride('commandBus', new FakeCommandBus(function ($command) use (&$recordedCommands) {
      $recordedCommands[] = $command;

      if ($command instanceof CreateUserCommand) {
        return new UserDto('10', $command->name, $command->email, $command->roleId);
      }

      return true;
    }));

    return $csrfToken;
  }

  private function executeUsersRoute(): string
  {
    try {
      require BASE_PATH . '/app/Presentation/pages/users-index.php';
    } catch (RuntimeException $exception) {
      if (str_starts_with($exception->getMessage(), 'REDIRECT:')) {
        return substr($exception->getMessage(), strlen('REDIRECT:'));
      }

      throw $exception;
    }

    $this->fail('Expected HTTP redirect in users-index route.');
  }

  /**
   * @return array<string,string>
   */
  private function parseRedirectQuery(string $location): array
  {
    $query = parse_url($location, PHP_URL_QUERY);
    $output = [];
    parse_str((string) $query, $output);
    return $output;
  }
}
