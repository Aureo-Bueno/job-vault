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
  ];

  public static function from(?string $status): ?array
  {
    if (!is_string($status) || $status === '') {
      return null;
    }

    return self::ALERTS[$status] ?? null;
  }
}
