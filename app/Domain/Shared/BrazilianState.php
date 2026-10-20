<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

/**
 * Brazilian federative units (UF).
 */
enum BrazilianState: string
{
  case AC = 'AC';
  case AL = 'AL';
  case AP = 'AP';
  case AM = 'AM';
  case BA = 'BA';
  case CE = 'CE';
  case DF = 'DF';
  case ES = 'ES';
  case GO = 'GO';
  case MA = 'MA';
  case MT = 'MT';
  case MS = 'MS';
  case MG = 'MG';
  case PA = 'PA';
  case PB = 'PB';
  case PR = 'PR';
  case PE = 'PE';
  case PI = 'PI';
  case RJ = 'RJ';
  case RN = 'RN';
  case RS = 'RS';
  case RO = 'RO';
  case RR = 'RR';
  case SC = 'SC';
  case SP = 'SP';
  case SE = 'SE';
  case TO = 'TO';

  public static function fromString(string $value): self
  {
    return self::tryFrom(strtoupper(trim($value)))
      ?? throw new InvalidValueException('UF inválida.');
  }

  public static function fromStored(?string $value): ?self
  {
    return $value === null ? null : self::tryFrom(strtoupper(trim($value)));
  }

  public function name(): string
  {
    return match ($this) {
      self::AC => 'Acre',
      self::AL => 'Alagoas',
      self::AP => 'Amapá',
      self::AM => 'Amazonas',
      self::BA => 'Bahia',
      self::CE => 'Ceará',
      self::DF => 'Distrito Federal',
      self::ES => 'Espírito Santo',
      self::GO => 'Goiás',
      self::MA => 'Maranhão',
      self::MT => 'Mato Grosso',
      self::MS => 'Mato Grosso do Sul',
      self::MG => 'Minas Gerais',
      self::PA => 'Pará',
      self::PB => 'Paraíba',
      self::PR => 'Paraná',
      self::PE => 'Pernambuco',
      self::PI => 'Piauí',
      self::RJ => 'Rio de Janeiro',
      self::RN => 'Rio Grande do Norte',
      self::RS => 'Rio Grande do Sul',
      self::RO => 'Rondônia',
      self::RR => 'Roraima',
      self::SC => 'Santa Catarina',
      self::SP => 'São Paulo',
      self::SE => 'Sergipe',
      self::TO => 'Tocantins',
    };
  }

  /**
   * @return array<string,string> UF => "UF - Name", ordered by UF
   */
  public static function options(): array
  {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->value . ' - ' . $case->name();
    }
    ksort($options);

    return $options;
  }
}
