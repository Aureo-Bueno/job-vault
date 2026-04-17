<?php

namespace App\Presentation\Support;

use App\Application\Abstractions\HandlerNotFoundException;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageAuthorizationException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\OperationFailedException;
use App\Application\Exceptions\UnauthorizedException;
use App\Db\Exceptions\DatabaseConnectionException;
use App\Db\Exceptions\DatabaseException;
use App\Db\Exceptions\DatabaseQueryException;
use InvalidArgumentException;
use Throwable;

final class ExceptionHttpMapper
{
  /**
   * @return array{httpCode:int,status:string,message:string}
   */
  public static function toPayload(Throwable $exception): array
  {
    if ($exception instanceof NotFoundException) {
      return [
        'httpCode' => 404,
        'status' => 'not_found',
        'message' => self::messageOrDefault($exception, 'Recurso não encontrado.'),
      ];
    }

    if ($exception instanceof UnauthorizedException) {
      return [
        'httpCode' => 401,
        'status' => 'unauthorized',
        'message' => self::messageOrDefault($exception, 'Você precisa entrar para continuar.'),
      ];
    }

    if ($exception instanceof MessageAuthorizationException || $exception instanceof ForbiddenException) {
      return [
        'httpCode' => 403,
        'status' => 'forbidden',
        'message' => self::messageOrDefault($exception, 'Você não tem permissão para executar esta ação.'),
      ];
    }

    if ($exception instanceof MessageValidationException || $exception instanceof InvalidArgumentException) {
      return [
        'httpCode' => 422,
        'status' => 'validation_error',
        'message' => self::messageOrDefault($exception, 'Existem dados inválidos na solicitação.'),
      ];
    }

    if ($exception instanceof DatabaseConnectionException) {
      return [
        'httpCode' => 503,
        'status' => 'db_error',
        'message' => 'Banco de dados indisponível no momento. Tente novamente em instantes.',
      ];
    }

    if ($exception instanceof DatabaseQueryException || $exception instanceof DatabaseException) {
      return [
        'httpCode' => 500,
        'status' => 'db_error',
        'message' => 'Falha ao processar dados no banco. Tente novamente em instantes.',
      ];
    }

    if ($exception instanceof OperationFailedException) {
      return [
        'httpCode' => 500,
        'status' => 'server_error',
        'message' => self::messageOrDefault($exception, 'Falha ao executar a operação solicitada.'),
      ];
    }

    if ($exception instanceof HandlerNotFoundException) {
      return [
        'httpCode' => 500,
        'status' => 'server_error',
        'message' => 'Operação indisponível no momento.',
      ];
    }

    return [
      'httpCode' => 500,
      'status' => 'server_error',
      'message' => 'Erro inesperado ao processar a solicitação.',
    ];
  }

  /**
   * @return array{tipo:string,icone:string,mensagem:string}
   */
  public static function toAlert(Throwable $exception): array
  {
    $payload = self::toPayload($exception);
    $alert = StatusAlertMapper::from($payload['status']);

    return [
      'tipo' => (string) ($alert['tipo'] ?? 'danger'),
      'icone' => (string) ($alert['icone'] ?? 'bi bi-exclamation-octagon-fill'),
      'mensagem' => $payload['message'],
    ];
  }

  private static function messageOrDefault(Throwable $exception, string $default): string
  {
    $message = trim($exception->getMessage());
    return $message !== '' ? $message : $default;
  }
}
