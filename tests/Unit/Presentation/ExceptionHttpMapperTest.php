<?php

namespace Tests\Unit\Presentation;

use App\Application\Exceptions\MessageAuthorizationException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\OperationFailedException;
use App\Db\Exceptions\DatabaseConnectionException;
use App\Db\Exceptions\DatabaseQueryException;
use App\Presentation\Support\ExceptionHttpMapper;
use PHPUnit\Framework\TestCase;

class ExceptionHttpMapperTest extends TestCase
{
  public function testMapsNotFoundTo404PayloadAndAlert(): void
  {
    $exception = new NotFoundException('Vaga não encontrada.');

    $payload = ExceptionHttpMapper::toPayload($exception);
    $alert = ExceptionHttpMapper::toAlert($exception);

    $this->assertSame(404, $payload['httpCode']);
    $this->assertSame('not_found', $payload['status']);
    $this->assertSame('Vaga não encontrada.', $payload['message']);
    $this->assertSame('warning', $alert['tipo']);
    $this->assertSame('Vaga não encontrada.', $alert['mensagem']);
  }

  public function testMapsValidationAndAuthorizationToExpectedHttpCodes(): void
  {
    $validation = ExceptionHttpMapper::toPayload(new MessageValidationException('Entrada inválida.'));
    $authorization = ExceptionHttpMapper::toPayload(new MessageAuthorizationException('Acesso negado.'));

    $this->assertSame(422, $validation['httpCode']);
    $this->assertSame('validation_error', $validation['status']);
    $this->assertSame('Entrada inválida.', $validation['message']);

    $this->assertSame(403, $authorization['httpCode']);
    $this->assertSame('forbidden', $authorization['status']);
    $this->assertSame('Acesso negado.', $authorization['message']);
  }

  public function testMapsDatabaseExceptionsToDbErrorStatus(): void
  {
    $connection = ExceptionHttpMapper::toPayload(new DatabaseConnectionException('Down.'));
    $query = ExceptionHttpMapper::toPayload(new DatabaseQueryException('Query failed.'));

    $this->assertSame(503, $connection['httpCode']);
    $this->assertSame('db_error', $connection['status']);

    $this->assertSame(500, $query['httpCode']);
    $this->assertSame('db_error', $query['status']);
  }

  public function testMapsOperationFailedExceptionToServerErrorWithMessage(): void
  {
    $payload = ExceptionHttpMapper::toPayload(new OperationFailedException('Não foi possível concluir a operação.'));

    $this->assertSame(500, $payload['httpCode']);
    $this->assertSame('server_error', $payload['status']);
    $this->assertSame('Não foi possível concluir a operação.', $payload['message']);
  }
}
