<?php

namespace App\Presentation\Support;

use App\Application\Abstractions\HandlerNotFoundException;
use App\Application\Exceptions\ConflictException;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\OperationFailedException;
use App\Application\Exceptions\UnauthorizedException;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Infrastructure\Persistence\Exceptions\DatabaseConnectionException;
use App\Infrastructure\Persistence\Exceptions\DatabaseException;
use InvalidArgumentException;
use Throwable;

/**
 * Translates exceptions into an HTTP code + UI status + user-facing message.
 */
final class ExceptionHttpMapper
{
  /**
   * @return array{httpCode:int,status:string,message:string}
   */
  public static function toPayload(Throwable $exception): array
  {
    return match (true) {
      $exception instanceof NotFoundException =>
        self::payload(404, 'not_found', self::messageOrDefault($exception, 'Recurso não encontrado.')),
      $exception instanceof UnauthorizedException =>
        self::payload(401, 'unauthorized', self::messageOrDefault($exception, 'Você precisa entrar para continuar.')),
      $exception instanceof ForbiddenException =>
        self::payload(403, 'forbidden', self::messageOrDefault($exception, 'Você não tem permissão para executar esta ação.')),
      $exception instanceof AlreadyExistsException,
      $exception instanceof ConflictException =>
        self::payload(409, 'conflict', self::messageOrDefault($exception, 'O registro informado já existe.')),
      $exception instanceof InvalidValueException,
      $exception instanceof BusinessRuleViolationException,
      $exception instanceof MessageValidationException,
      $exception instanceof InvalidArgumentException =>
        self::payload(422, 'validation_error', self::messageOrDefault($exception, 'Existem dados inválidos na solicitação.')),
      $exception instanceof DatabaseConnectionException =>
        self::payload(503, 'db_error', 'Banco de dados indisponível no momento. Tente novamente em instantes.'),
      $exception instanceof DatabaseException =>
        self::payload(500, 'db_error', 'Falha ao processar dados no banco. Tente novamente em instantes.'),
      $exception instanceof OperationFailedException =>
        self::payload(500, 'server_error', self::messageOrDefault($exception, 'Falha ao executar a operação solicitada.')),
      $exception instanceof HandlerNotFoundException =>
        self::payload(500, 'server_error', 'Operação indisponível no momento.'),
      default =>
        self::payload(500, 'server_error', 'Erro inesperado ao processar a solicitação.'),
    };
  }

  /**
   * @return array{tipo:string,icone:string,mensagem:string}
   */
  public static function toAlert(Throwable $exception): array
  {
    $payload = self::toPayload($exception);
    $alert = StatusAlertMapper::from($payload['status']) ?? StatusAlertMapper::from('error');

    return [
      'tipo' => (string) ($alert['tipo'] ?? 'danger'),
      'icone' => (string) ($alert['icone'] ?? 'bi bi-exclamation-octagon-fill'),
      'mensagem' => $payload['message'],
    ];
  }

  public static function isUnexpected(Throwable $exception): bool
  {
    return self::toPayload($exception)['httpCode'] >= 500;
  }

  /**
   * @return array{httpCode:int,status:string,message:string}
   */
  private static function payload(int $httpCode, string $status, string $message): array
  {
    return ['httpCode' => $httpCode, 'status' => $status, 'message' => $message];
  }

  private static function messageOrDefault(Throwable $exception, string $default): string
  {
    $message = trim($exception->getMessage());
    return $message !== '' ? $message : $default;
  }
}
