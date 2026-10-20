<?php

namespace Tests\Unit\Presentation;

use App\Application\Exceptions\ConflictException;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\OperationFailedException;
use App\Domain\JobApplication\AlreadyAppliedException;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Infrastructure\Persistence\Exceptions\DatabaseConnectionException;
use App\Infrastructure\Persistence\Exceptions\DatabaseQueryException;
use App\Presentation\Support\ExceptionHttpMapper;
use PHPUnit\Framework\TestCase;

final class ExceptionHttpMapperTest extends TestCase
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
    $authorization = ExceptionHttpMapper::toPayload(new ForbiddenException('Acesso negado.'));

    $this->assertSame(422, $validation['httpCode']);
    $this->assertSame('validation_error', $validation['status']);
    $this->assertSame(403, $authorization['httpCode']);
    $this->assertSame('forbidden', $authorization['status']);
    $this->assertSame('Acesso negado.', $authorization['message']);
  }

  public function testMapsDomainExceptions(): void
  {
    $rule = ExceptionHttpMapper::toPayload(new VacancyNotOpenException());
    $conflict = ExceptionHttpMapper::toPayload(new AlreadyAppliedException());

    $this->assertSame(422, $rule['httpCode']);
    $this->assertSame('validation_error', $rule['status']);
    $this->assertSame(409, $conflict['httpCode']);
    $this->assertSame('conflict', $conflict['status']);
    $this->assertSame('Você já se candidatou a esta vaga.', $conflict['message']);
  }

  public function testMapsStaleDataConflictTo409(): void
  {
    $payload = ExceptionHttpMapper::toPayload(new ConflictException('Movida por outra pessoa.'));

    $this->assertSame(409, $payload['httpCode']);
    $this->assertSame('conflict', $payload['status']);
    $this->assertSame('Movida por outra pessoa.', $payload['message']);
  }

  public function testMapsDatabaseExceptionsWithoutLeakingDetails(): void
  {
    $connection = ExceptionHttpMapper::toPayload(new DatabaseConnectionException('SQLSTATE secret'));
    $query = ExceptionHttpMapper::toPayload(new DatabaseQueryException('SQLSTATE secret'));

    $this->assertSame(503, $connection['httpCode']);
    $this->assertSame('db_error', $connection['status']);
    $this->assertStringNotContainsString('SQLSTATE', $connection['message']);
    $this->assertSame(500, $query['httpCode']);
    $this->assertTrue(ExceptionHttpMapper::isUnexpected(new DatabaseQueryException()));
  }

  public function testMapsOperationFailedAndUnknownToServerError(): void
  {
    $failed = ExceptionHttpMapper::toPayload(new OperationFailedException('Não foi possível concluir a operação.'));
    $unknown = ExceptionHttpMapper::toPayload(new \RuntimeException('boom'));

    $this->assertSame('server_error', $failed['status']);
    $this->assertSame('Não foi possível concluir a operação.', $failed['message']);
    $this->assertSame(500, $unknown['httpCode']);
    $this->assertStringNotContainsString('boom', $unknown['message']);
  }
}
