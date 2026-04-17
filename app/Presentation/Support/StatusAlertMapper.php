<?php

namespace App\Presentation\Support;

final class StatusAlertMapper
{
  private const ALERTS = [
    'success' => [
      'tipo' => 'success',
      'icone' => 'bi bi-check-circle-fill',
      'mensagem' => 'Ação executada com sucesso!',
    ],
    'error' => [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível executar ação!',
    ],
    'exists' => [
      'tipo' => 'info',
      'icone' => 'bi bi-info-circle-fill',
      'mensagem' => 'Você já se candidatou a esta vaga.',
    ],
    'not_found' => [
      'tipo' => 'warning',
      'icone' => 'bi bi-search',
      'mensagem' => 'Recurso não encontrado.',
    ],
    'validation_error' => [
      'tipo' => 'warning',
      'icone' => 'bi bi-exclamation-triangle-fill',
      'mensagem' => 'Existem dados inválidos na solicitação.',
    ],
    'forbidden' => [
      'tipo' => 'danger',
      'icone' => 'bi bi-shield-lock-fill',
      'mensagem' => 'Você não tem permissão para executar esta ação.',
    ],
    'unauthorized' => [
      'tipo' => 'warning',
      'icone' => 'bi bi-person-lock',
      'mensagem' => 'Você precisa entrar para continuar.',
    ],
    'db_error' => [
      'tipo' => 'danger',
      'icone' => 'bi bi-database-exclamation',
      'mensagem' => 'Falha de infraestrutura de dados. Tente novamente em instantes.',
    ],
    'server_error' => [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-octagon-fill',
      'mensagem' => 'Erro interno ao processar a solicitação.',
    ],
  ];

  public static function from(?string $status): ?array
  {
    if (!is_string($status) || $status === '') {
      return null;
    }

    return self::ALERTS[$status] ?? null;
  }
}
